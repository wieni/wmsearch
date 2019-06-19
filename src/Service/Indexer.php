<?php

namespace Drupal\wmsearch\Service;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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

    protected function getEntityTypes(): array
    {
        return [
            'node',
            'taxonomy_term',
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

    public function queueAll($from, $limit, $offset, $entityType = null)
    {
        if ($entityType) {
            $entityTypes = [$entityType];
        } else {
            $entityTypes = $this->getEntityTypes();
        }

        foreach ($entityTypes as $entityTypeId) {
            try {
                $definition = $this->entityTypeManager->getDefinition($entityTypeId);
            } catch (PluginNotFoundException $e) {
                continue;
            }

            printf("Queuing entities of type %s\n", $definition->getLowercaseLabel());

            $storage = $this->entityTypeManager->getStorage($entityTypeId);
            $query = $storage->getQuery();
            if ($definition->hasKey('published')) {
                $query->condition($definition->getKey('published'), 1);
            }
            if ($from) {
                $query->condition($definition->getKey('id'), $from, '<=');
            }
            if ($limit) {
                $query->range($offset, $limit);
            }
            $query->sort($definition->getKey('id'), 'DESC');
            $ids = $query->execute();

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
