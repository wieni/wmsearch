<?php

namespace Drupal\wmsearch\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\wmsearch\Entity\Document\DocumentInterface;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\wmsearch\Service\Api\IndexApi;
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

    public function __construct(
        array $configuration,
        $pluginId,
        $pluginDef,
        IndexApi $indexApi,
        EntityTypeManagerInterface $entityTypeManager
    ) {
        parent::__construct($configuration, $pluginId, $pluginDef);

        $this->indexApi = $indexApi;
        $this->entityTypeManager = $entityTypeManager;
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
            $container->get('entity_type.manager')
        );
    }

    public function processItem($data)
    {
        $entity = $this->entityTypeManager->getStorage($data['type'])->load($data['id']);

        if (
            $entity
            && $entity instanceof TranslatableInterface
            && $entity->language()->getId() !== $data['language']
        ) {
            $entity = $entity->getTranslation($data['language']);
        }

        if (
            !$entity
            || !($entity instanceof DocumentInterface)
            || ($entity instanceof EntityPublishedInterface && !$entity->isPublished())
        ) {
            if ($elasticId = $data['types']['page'] ?? '') {
                $this->indexApi->delDoc('page', $elasticId);
            }

            return;
        }

        $this->indexApi->addDoc($entity);
    }
}

