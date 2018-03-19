<?php

namespace Drupal\wmsearch\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\node\Entity\Node;
use Drupal\wmsearch\Entity\Document\DocumentInterface;
use GuzzleHttp\Exception\GuzzleException;

class Indexer
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var Api */
    protected $index;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        Api $index
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->index = $index;
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

            $total = count($ids);
            $c = 0;

            foreach (array_chunk($ids, 50) as $chunk) {
                $this->resetCaches();
                foreach ($chunk as $id) {
                    $c++;
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
        $this->index->deleteIndex();
        $this->index->createIndex();
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

    private function indexDocument($entity)
    {
        if (!$entity instanceof DocumentInterface) {
            return;
        }

        $data['types'] = $entity->getElasticTypes();

        // Separate 'related' type, we'll handle it differently
        $types = array_diff($data['types'], ['related']);
        if ($types) {
            $this->index->addDoc($entity, $types);
        }

        $isDefaultLanguage = !$entity instanceOf TranslatableInterface
            || !$entity->isTranslatable()
            || $entity->isDefaultTranslation()
            || count($entity->getTranslationLanguages()) === 1;

        // Only index 'related' type in the default language
        if ($isDefaultLanguage && in_array('related', $data['types'])) {
            $this->index->addDoc($entity, ['related']);
        }
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
