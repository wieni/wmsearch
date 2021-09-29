<?php

namespace Drupal\wmsearch\Service\Api;

use Drupal\Core\File\FileSystem;
use Drupal\Core\State\StateInterface;
use Drupal\wmsearch\Entity\Document\DocumentInterface;
use Drupal\wmsearch\Exception\ApiException;
use Drupal\wmsearch\Exception\NotIndexableException;
use Drupal\wmsearch\WmsearchEvents;
use Drupal\wmsearch\Event\MappingEvent;
use Drupal\Core\Extension\ModuleHandlerInterface;
use GuzzleHttp\Psr7\PumpStream;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class IndexApi extends BaseApi
{
    /** @var string */
    protected $cwd;
    /** @var string */
    protected $index;
    /** @var array */
    protected $baseMapping;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;
    /** @var FileSystem */
    protected $fileSystem;
    /** @var StateInterface */
    protected $state;
    /** @var AliasApi */
    protected $aliasApi;
    /** @var ReindexApi */
    protected $reindexApi;
    /** @var TaskApi */
    protected $taskApi;

    public function __construct(
        $endpoint,
        $appRoot,
        $index,
        ModuleHandlerInterface $moduleHandler,
        EventDispatcherInterface $eventDispatcher,
        FileSystem $fileSystem,
        StateInterface $state,
        AliasApi $aliasApi,
        ReindexApi $reindexApi,
        TaskApi $taskApi
    ) {
        parent::__construct($endpoint);

        if (empty($index)) {
            throw new \InvalidArgumentException(
                'The elastic index name cannot be empty'
            );
        }

        $this->cwd = $appRoot . DIRECTORY_SEPARATOR . $moduleHandler->getModule('wmsearch')->getPath();
        $this->index = $index;
        $this->eventDispatcher = $eventDispatcher;
        $this->fileSystem = $fileSystem;
        $this->state = $state;
        $this->aliasApi = $aliasApi;
        $this->reindexApi = $reindexApi;
        $this->taskApi = $taskApi;
    }

    protected function getBaseMapping()
    {
        if (isset($this->baseMapping)) {
            return $this->baseMapping;
        }

        $mapping = json_decode(
            file_get_contents(
                $this->cwd .
                DIRECTORY_SEPARATOR .
                'assets' .
                DIRECTORY_SEPARATOR .
                'mapping.json'
            ),
            true
        );

        if (!$mapping) {
            throw new \RuntimeException('Could not fetch base mapping');
        }

        if ($synonyms = $this->state->get('wmsearch.synonyms')) {
            $mapping['settings']['analysis']['filter']['synonym']['synonyms'] = $synonyms;
        }

        $event = new MappingEvent($mapping);
        $this->eventDispatcher->dispatch(WmsearchEvents::MAPPING, $event);

        return $this->baseMapping = $event->getMapping();
    }

    public function createIndex($recreate = false)
    {
        if ($this->indexExists() && !$recreate) {
            return;
        }

        // Create new index and assign an alias
        $newIndex = sprintf('%s-%s', $this->index, time());
        $mapping = $this->getBaseMapping();
        $this->put($newIndex, $mapping);

        // If an index/alias exists with the same name as the new index:
        // - fill the new index with entries from the existing index/alias
        // - remove the existing index (or in case it is an alias,
        //   remove all indexes attached to the alias)
        if ($this->indexExists()) {
            $taskId = $this->reindexApi->reindex($this->index, $newIndex);
            $this->taskApi->waitForCompletion($taskId);
            $this->deleteIndex();
        }

        // Assign the alias to the new index
        $this->aliasApi->addAlias($newIndex, $this->index);
    }

    public function indexExists()
    {
        try {
            $this->health();
        } catch (ApiException $exception) {
            if ($exception->isIndexNotFound()) {
                return false;
            }

            throw $exception;
        }

        return true;
    }

    public function deleteIndex()
    {
        $this->delete($this->index);
    }

    /**
     * Simple http health check
     */
    public function health()
    {
        $this->get($this->index);
    }

    /**
     * Makes sure every document added up until now is searchable.
     */
    public function refresh()
    {
        return $this->post(
            sprintf('%s/_refresh', $this->index)
        );
    }

    /**
     * Make sure data is committed to disk. (slow)
     *
     * Lucene commit. i.e.: fsync
     */
    public function flush()
    {
        return $this->post(
            sprintf('%s/_flush', $this->index),
            ['wait_if_ongoing' => true]
        );
    }

    public function recreate()
    {
        try {
            $this->deleteIndex();
        } catch (ApiException $exception) {
            if (!$exception->isIndexNotFound()) {
                throw $exception;
            }
        }

        $this->createIndex();
    }

    public function addDoc(DocumentInterface $doc, array $docTypes = [])
    {
        $docTypes = $docTypes ?: $doc->getElasticTypes();

        foreach ($docTypes as $type) {
            try {
                $arr = $doc->toElasticArray($type);
                $this->put(
                    sprintf(
                        '%s/%s/%s',
                        $this->index,
                        $type,
                        $doc->getElasticId($type)
                    ),
                    $arr
                );
            } catch (NotIndexableException $e) {
                $this->delDoc($type, $doc->getElasticId($type));
            }
        }
    }

    /**
     * @param DocumentInterface[] $docs List of documents to update
     *                                  using the _bulk api.
     */
    public function addDocs(array $docs)
    {
        $_docs = [];
        foreach ($docs as $i => $doc) {
            if (!($doc instanceof DocumentInterface)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Document at index `%s` of type `%s` does not implement DocumentInterface',
                        $i,
                        get_class($doc)
                    )
                );
            }

            foreach ($doc->getElasticTypes() as $type) {
                try {
                    $_docs[] = [
                        'type' => $type,
                        'id' => $doc->getElasticId($type),
                        'arr' => $doc->toElasticArray($type),
                    ];
                } catch (NotIndexableException $e) {
                    $this->delDoc($type, $doc->getElasticId($type));
                }
            }
        }

        $i = 0;
        $stream = new PumpStream(
            function () use (&$i, $_docs) {
                if (!isset($_docs[$i])) {
                    return false;
                }

                $doc = $_docs[$i];
                $i++;

                return json_encode(
                        [
                            'index' => [
                                '_type' => $doc['type'],
                                '_id' => $doc['id'],
                            ],
                        ]
                    ) .
                    "\n" .
                    json_encode($doc['arr']) .
                    "\n";
            }
        );

        $this->exec(
            sprintf('%s/_bulk', $this->index),
            'PUT',
            ['body' => $stream]
        );
    }

    public function getDoc($docType, $id)
    {
        return $this->get(sprintf('%s/%s/%s', $this->index, $docType, $id));
    }

    public function delDoc($docType, $id)
    {
        try {
            return $this->delete(sprintf('%s/%s/%s', $this->index, $docType, $id));
        } catch (ApiException $e) {
            if (!$e->isNotFound()) {
                throw $e;
            }
        }

        return $this;
    }

    public function getMapping()
    {
        $data = $this->get(
            sprintf('%s/_mapping', $this->index)
        );
        $aliasName = key($data);

        return $data[$aliasName]['mappings']['page'] ?? [];
    }

    public function getSettings()
    {
        $data = $this->get(
            sprintf('%s/_settings', $this->index)
        );
        $aliasName = key($data);

        return $data[$aliasName]['settings'];
    }

    public function getIndexName()
    {
        return $this->index;
    }

    /** @param string $index */
    public function setIndexName($index)
    {
        $this->index = $index;
    }

    public function setReindexNeeded(bool $value = true): void
    {
        $this->state->set('wmsearch.reindexNeeded', $value);
    }

    public function isReindexNeeded(): bool
    {
        return $this->state->get('wmsearch.reindexNeeded', false);
    }
}
