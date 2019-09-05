<?php

namespace Drupal\wmsearch\Service;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\wmsearch\Service\Api\IndexApi;

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
        $entityTypeIds = [];
        foreach ($this->entityTypeManager->getDefinitions() as $definition) {
            if ($definition instanceof ContentEntityTypeInterface) {
                $entityTypeIds[] = $definition->id();
            }
        }

        return array_map(
            function ($entityTypeId) {
                return $this->entityTypeManager->getStorage($entityTypeId);
            },
            $entityTypeIds
        );
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
            $i = 0;
            foreach (array_chunk($ids, 50) as $chunk) {
                $this->resetCaches();
                foreach ($chunk as $id) {
                    $entity = $storage->load($id);
                    printf(
                        "\033[0G\033[K%03d/%03d (%s) - %s",
                        ++$i,
                        $total,
                        $id,
                        $entity->label()
                    );

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
            dump($e->getMessage());
        }
        $this->indexApi->createIndex();
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
