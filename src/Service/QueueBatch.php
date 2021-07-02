<?php

namespace Drupal\wmsearch\Service;

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
    protected const QUEUE_NAME = 'wmsearch.index';

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

    public function __construct(
        LoggerChannelFactoryInterface $loggerChannelFactory,
        EntityTypeManagerInterface $entityTypeManager,
        MemoryCacheInterface $memoryCache,
        MessengerInterface $messenger
    ) {
        $this->loggerChannelFactory = $loggerChannelFactory;
        $this->entityTypeManager = $entityTypeManager;
        $this->memoryCache = $memoryCache;
        $this->messenger = $messenger;
    }

    public function run(string $entityTypeId = null, int $from = 0, int $limit = 0, int $offset = 0): void
    {
        if ($entityTypeId) {
            $operations = [
                [$this, 'step'],
                [$entityTypeId, $this->getIds($entityTypeId, $from, $limit, $offset)],
            ];
        } else {
            $operations = array_map(
                function (string $entityTypeId) {
                    return [
                        [$this, 'step'],
                        [$entityTypeId, $this->getIds($entityTypeId)],
                    ];
                },
                $this->getEntityTypes()
            );
        }

        $batch = [
            'title' => $this->t('Processing queue.'),
            'operations' => $operations,
            'finished' => [$this, 'finish'],
        ];

        batch_set($batch);
    }

    public function step(string $entityTypeId, array $ids, array &$context = []): void
    {
        try {
            $definition = $this->entityTypeManager->getDefinition($entityTypeId);
        } catch (PluginNotFoundException $e) {
            return;
        }

        $context['finished'] = 0;

        if (empty($context['sandbox']['ids'])) {
            $context['sandbox']['ids'] = $ids;
        }

        if (empty($context['results']['processed'])) {
            $context['results']['processed'] = 0;
        }

        $context['message'] = $this->t('Queuing entities of type %entityType: %count remaining.', [
            '%entityType' => $definition->getLowercaseLabel(),
            '%count' => count($ids),
        ]);

        $id = array_pop($context['sandbox']['ids']);
        $storage = $this->entityTypeManager->getStorage($entityTypeId);
        $entity = $storage->load($id);
        wmsearch_queue($entity, true);

        $context['results']['processed']++;

        if (empty($context['sandbox']['ids'])) {
            $context['finished'] = 1;
        }
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

    protected function getEntityTypes(): array
    {
        return [
            'node',
            'taxonomy_term',
        ];
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

        $query->sort($definition->getKey('id'), 'DESC');

        return $query->execute();
    }
}
