<?php

namespace Drupal\wmsearch\Plugin\QueueWorker;

use Drupal\wmsearch\Service\Api;
use Drupal\wmsearch\Entity\Document\DocumentInterface;

use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\TypedData\TranslatableInterface;
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
    /** @var Api */
    protected $api;

    /** @var EntityTypeManagerInterface */
    protected $etm;

    public function __construct(
        array $configuration,
        $pluginId,
        $pluginDef,
        Api $api,
        EntityTypeManagerInterface $etm
    ) {
        parent::__construct($configuration, $pluginId, $pluginDef);

        $this->api = $api;
        $this->etm = $etm;
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
            $container->get('wmsearch.api'),
            $container->get('entity_type.manager')
        );
    }

    public function processItem($data)
    {
        $entity = $this->etm->getStorage($data['type'])->load($data['id']);

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
            || ($entity instanceof NodeInterface && !$entity->isPublished())
        ) {
            $this->api->delDoc(
                'page',
                wmsearch_id($data['type'], $data['language'], $data['id'])
            );

            return;
        }

        $this->api->addDoc($entity);
    }
}

