<?php

namespace Drupal\wmsearch\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\wmsearch\Entity\Document\ElasticEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\wmsearch\Service\Api\IndexApi;
use Drupal\wmsearch\Service\DocumentCollectionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @QueueWorker(
 *   id = "wmsearch.index",
 *   title = "Elastic index queue",
 *   cron = {"time" = 30}
 * )
 */
class IndexWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface
{
    /** @var IndexApi */
    protected $indexApi;
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var DocumentCollectionManager */
    protected $collectionManager;

    public function __construct(
        array $configuration,
        $pluginId,
        $pluginDef,
        IndexApi $indexApi,
        EntityTypeManagerInterface $entityTypeManager,
        DocumentCollectionManager $collectionManager
    ) {
        parent::__construct($configuration, $pluginId, $pluginDef);

        $this->indexApi = $indexApi;
        $this->entityTypeManager = $entityTypeManager;
        $this->collectionManager = $collectionManager;
    }

    public static function create(
        ContainerInterface $container,
        array $configuration,
        $pluginId,
        $pluginDef
    ) {
        return new static(
            $configuration,
            $pluginId,
            $pluginDef,
            $container->get('wmsearch.api.index'),
            $container->get('entity_type.manager'),
            $container->get('wmsearch.document_collection.manager'),
        );
    }

    public function processItem($data)
    {
        $entity = $this->entityTypeManager->getStorage($data['type'])->load($data['id']);

        if (
            $entity instanceof TranslatableInterface
            && $entity->language()->getId() !== $data['language']
            && $entity->hasTranslation($data['language'])
        ) {
            $entity = $entity->getTranslation($data['language']);
        }

        if (
            !$entity
            || !($entity instanceof ElasticEntityInterface)
            || ($entity instanceof EntityPublishedInterface && !$entity->isPublished())
        ) {
            foreach ($data['types'] as $type => $info) {
                if (!empty($info['elasticIds'])) {
                    $this->collectionManager->resetIndexedIds($info['collection']);
                    foreach ($info['elasticIds'] as $elasticId) {
                        $this->indexApi->delDoc($elasticId);
                    }
                }
            }

            return;
        }

        $this->indexApi->addDoc($entity);
    }
}

