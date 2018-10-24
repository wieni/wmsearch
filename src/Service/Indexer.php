<?php

namespace Drupal\wmsearch\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\node\Entity\Node;
use Drupal\wmsearch\Entity\Document\DocumentInterface;
use Drupal\wmsearch\Service\Api\IndexApi;
use GuzzleHttp\Exception\GuzzleException;

class Indexer
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var IndexApi */
    protected $indexApi;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        IndexApi $indexApi
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->indexApi = $indexApi;
    }

    protected function getConditions($from, $limit, $offset)
    {
        return [
            'node' => function (QueryInterface $qb) use ($from, $limit, $offset) {
                $qb->condition('status', 1);
                if ($from) {
                    $qb->condition('nid', $from, '<=');
                }
                if ($limit) {
                    $qb->range($offset, $limit);
                }
                $qb->sort('nid', 'DESC');
            },
            'taxonomy_term' => function (QueryInterface $qb) use ($from, $limit, $offset) {
                if ($from) {
                    $qb->condition('tid', $from, '<=');
                }
                if ($limit) {
                    $qb->range($offset, $limit);
                }
                $qb->sort('tid', 'DESC');
            },
        ];
    }

    protected function getStorages()
    {
        return [
            $this->entityTypeManager->getStorage('node'),
            $this->entityTypeManager->getStorage('taxonomy_term'),
        ];
    }

    public function queueAll($from, $limit, $offset)
    {
        $conditions = $this->getConditions($from, $limit, $offset);

        foreach ($conditions as $et => $condition) {
            printf("Queuing entities of type %s\n", $et);

            $storage = $this->entityTypeManager->getStorage($et);
            $qb = $storage->getQuery();
            $condition($qb);
            $ids = $qb->execute();

            $total = max($ids);

            foreach (array_chunk($ids, 50) as $chunk) {
                $this->resetCaches();
                foreach ($chunk as $id) {
                    $entity = $storage->load($id);
                    printf(
                        "\033[0G\033[K%03d/%03d",
                        $id,
                        $total
                    );

                    if (!$entity instanceof DocumentInterface) {
                        continue;
                    }

                    wmsearch_queue($entity, true);
                }
            }

            echo "\n[done]\n";
        }
    }

    public function purge()
    {
        try {
            $this->indexApi->deleteIndex();
        } catch (\Exception $e) {
        }
        $this->indexApi->createIndex();
    }

    private function indexEntity(EntityInterface $entity)
    {
        static $fails;

        $retries = 3;

        if (!isset($fails)) {
            $fails = 0;
        }

        try {
            if ($entity instanceof TranslatableInterface) {
                $this->indexTranslations($entity);
                return;
            }

            $this->indexDocument($entity);
        } catch (GuzzleException $e) {
            $fails++;
            if ($fails >= $retries) {
                throw $e;
            }
            dump($e->getMessage());
            sleep(1);
            $this->indexEntity($entity);
            return;
        }
        $fails = 0;
    }

    private function indexTranslations(TranslatableInterface $entity)
    {
        foreach ($entity->getTranslationLanguages() as $langId => $_) {
            $translation = $entity->getTranslation($langId);
            if ($translation instanceof Node && !$translation->isPublished()) {
                continue;
            }
            $this->indexDocument($translation);
        }
    }

    protected function indexDocument($entity)
    {
        if (!$entity instanceof DocumentInterface) {
            return;
        }

        $this->indexApi->addDoc($entity, $entity->getElasticTypes());
    }

    private function resetCaches()
    {
        static $storages;

        if (!isset($storages)) {
            $storages = $this->getStorages();
        }

        array_map([$this, 'resetCache'], $storages);
    }

    private function resetCache(EntityStorageInterface $storage)
    {
        $storage->resetCache();
    }
}
