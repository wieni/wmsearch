<?php

namespace Drupal\wmsearch\Service\Batch;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class QueueBatch
{
    protected const CHUNK_SIZE = 20;

    use DependencySerializationTrait;
    use StringTranslationTrait;

    /** @var LoggerChannelInterface */
    protected $loggerChannelFactory;
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var MemoryCacheInterface */
    protected $memoryCache;
    /** @var MessengerInterface */
    protected $messenger;
    /** @var string[] */
    protected $entityTypes;

    public function __construct(
        LoggerChannelFactoryInterface $loggerChannelFactory,
        EntityTypeManagerInterface $entityTypeManager,
        MemoryCacheInterface $memoryCache,
        MessengerInterface $messenger,
        array $entityTypes
    ) {
        $this->loggerChannelFactory = $loggerChannelFactory;
        $this->entityTypeManager = $entityTypeManager;
        $this->memoryCache = $memoryCache;
        $this->messenger = $messenger;
        $this->entityTypes = $entityTypes;
    }

    public function get(string $entityTypeId = null, int $from = 0, int $limit = 0, int $offset = 0): array
    {
        if ($entityTypeId) {
            $entityTypeIds = [$entityTypeId];
        } else {
            $entityTypeIds = $this->entityTypes;
        }

        $operations = [];
        $count = 0;

        foreach ($entityTypeIds as $entityTypeId) {
            $ids = $this->getIds($entityTypeId, $from, $limit, $offset);
            $count += count($ids);

            foreach (array_chunk($ids, self::CHUNK_SIZE) as $chunk) {
                $operations[] = [
                    [$this, 'step'],
                    [$entityTypeId, $chunk],
                ];
            }
        }

        foreach ($operations as &$operation) {
            $operation[1][] = $count;
        }

        return [
            'title' => $this->t('Processing queue.'),
            'operations' => $operations,
            'finished' => [$this, 'finish'],
        ];
    }

    public function step(string $entityTypeId, array $ids, int $total, &$context = []): void
    {
        try {
            $definition = $this->entityTypeManager->getDefinition($entityTypeId);
        } catch (PluginNotFoundException $e) {
            return;
        }

        if (empty($context['results']['processed'])) {
            $context['results']['processed'] = 0;
        }

        $context['results']['processed'] += count($ids);

        $context['message'] = $this->t('Queuing entities of type %entityType: %count remaining.', [
            '%entityType' => $definition->getLabel(),
            '%count' => $total - $context['results']['processed'],
        ]);

        $storage = $this->entityTypeManager->getStorage($entityTypeId);

        foreach ($storage->loadMultiple($ids) as $entity) {
            wmsearch_queue($entity, true);
        }

        $this->memoryCache->deleteAll();
        gc_collect_cycles();
    }

    public function finish(bool $success, array $results): void
    {
        $this->messenger->addStatus(
            $this->formatPlural(
                $results['processed'],
                'One document successfully queued.',
                '@count documents successfully queued.'
            )
        );

        if (!empty($results['errors'])) {
            $this->messenger->addStatus(
                $this->formatPlural(
                    count($results['errors']),
                    'An error occurred while queueing documents: @errors',
                    '@count errors occurred while queueing documents: <ul><li>@errors</li></ul>',
                    [
                        '@errors' => Markup::create(implode('</li><li>', $results['errors'])),
                    ]
                ),
                'error'
            );
        }
    }

    protected function getIds(string $entityTypeId, int $from = 0, int $limit = 0, int $offset = 0): array
    {
        $definition = $this->entityTypeManager->getDefinition($entityTypeId);
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

        $query->accessCheck(false);
        $query->sort($definition->getKey('id'), 'DESC');

        return $query->execute();
    }
}
