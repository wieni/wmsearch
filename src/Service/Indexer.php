<?php

namespace Drupal\wmsearch\Service;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\wmsearch\Service\Api\IndexApi;

class Indexer
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var MemoryCacheInterface */
    protected $memoryCache;
    /** @var IndexApi */
    protected $indexApi;
    /** @var string[] */
    protected $entityTypes;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        MemoryCacheInterface $memoryCache,
        IndexApi $indexApi,
        array $entityTypes
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->memoryCache = $memoryCache;
        $this->indexApi = $indexApi;
        $this->entityTypes = $entityTypes;
    }

    protected function getEntityTypes(): array
    {
        return $this->entityTypes;
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

            printf("Queuing entities of type %s\n", $definition->getSingularLabel());

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
                $this->memoryCache->deleteAll();

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
}
