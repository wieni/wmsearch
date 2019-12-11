<?php

namespace Drupal\wmsearch\Commands;

use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\wmsearch\Service\Api\IndexApi;
use Drupal\wmsearch\Service\Api\ReindexApi;
use Drupal\wmsearch\Service\Api\TaskApi;
use Drupal\wmsearch\Service\Indexer;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Input\InputInterface;

class IndexCommands extends DrushCommands
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var Indexer */
    protected $indexer;
    /** @var IndexApi */
    protected $indexApi;
    /** @var ReindexApi */
    protected $reindexApi;
    /** @var TaskApi */
    protected $taskApi;
    /** @var string */
    protected $defaultIndex;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        Indexer $indexer,
        IndexApi $indexApi,
        ReindexApi $reindexApi,
        TaskApi $taskApi,
        $defaultIndex
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->indexer = $indexer;
        $this->indexApi = $indexApi;
        $this->reindexApi = $reindexApi;
        $this->taskApi = $taskApi;
        $this->defaultIndex = $defaultIndex;
    }

    /**
     * Whimsical queuing of all entities
     *
     * @command wmsearch:queue
     * @aliases wmsearch-queue,wmsq
     *
     * @option from Continue from last id
     * @option limit Amount of items you have to process
     * @option offset Amount of items you have to process
     * @option entity-type The entity type that should be indexed
     */
    public function queue($options = ['from' => '0', 'limit' => '0', 'offset' => '0', 'entity-type' => ''])
    {
        $this->indexer->queueAll($options['from'], $options['limit'], $options['offset'], $options['entity-type']);
    }

    /**
     * @hook validate wmsearch:queue
     */
    public function validateQueue(CommandData $commandData)
    {
        $options = $commandData->options();

        if ($options['from'] && !$options['entity-type']) {
            throw new \InvalidArgumentException('The entity-type option is required when the from option is specified.');
        }

        if ($options['entity-type']) {
            try {
                $this->entityTypeManager->getDefinition($options['entity-type']);
            } catch (PluginNotFoundException $e) {
                throw new \InvalidArgumentException(sprintf('Entity type with id %s does not exist.', $options['entity-type']));
            }
        }
    }

    /**
     * KILL IT WITH FIRE
     *
     * @command wmsearch:purge
     * @aliases wmsearch-purge,wmsp
     */
    public function purge()
    {
        $this->indexer->purge();
    }

    /**
     * Reindexes an index to another index
     *
     * @command wmsearch:reindex
     * @aliases wmsearch-reindex,wmsri
     *
     * @param string $sourceIndex
     * @param string $destIndex
     * @param array $options
     */
    public function reindex($sourceIndex, $destIndex, $options = ['types' => 'page'])
    {
        $types = explode(',', $options['types']);
        $types = array_map('trim', $types);

        $taskId = $this->reindexApi->reindex($sourceIndex, $destIndex, $types);
        $this->displayTaskProgress($taskId);

        $this->logger()->info(
            sprintf('Successfully reindexed from %s to %s', $sourceIndex, $destIndex)
        );
    }

    /**
     * Create an index
     *
     * @command wmsearch:index-create
     * @aliases wmsearch-index-create,wmsc
     *
     * @option recreate
     *
     * @param string $index
     * @param array $options
     */
    public function indexCreate($index, $options = ['recreate' => false])
    {
        if ($this->indexApi->indexExists() && !$options['recreate']) {
            $this->logger()->warning(
                sprintf('Index with name %s already exists. Run this command with the --recreate option if you want to recreate the index.', $index)
            );
            return;
        }

        $oldIndex = $this->indexApi->getIndexName();
        $this->indexApi->setIndexName($index);
        $this->indexApi->createIndex($options['recreate']);
        $this->indexApi->setIndexName($oldIndex);

        $this->logger()->notice(
            sprintf(
                'Successfully %s index with name %s',
                $options['recreate'] ? 'recreated' : 'created',
                $index
            )
        );
    }

    /**
     * @hook init wmsearch:index-create
     */
    public function initIndexCreate(InputInterface $input, AnnotationData $annotationData)
    {
        $value = $input->getArgument('index');
        if (!$value) {
            $input->setArgument('index', $this->defaultIndex);
        }
    }

    protected function displayTaskProgress(string $taskId)
    {
        $this->logger->debug(
            sprintf('Running elastic task with id %s', $taskId)
        );

        $this->taskApi->waitForCompletion($taskId);
        $result = $this->taskApi->getTask($taskId);

        if (!empty($result['response']['failures'])) {
            $this->logger->error(
                sprintf('Task %d aborted with %d failures.', $taskId, count($result['response']['failures']))
            );
        } elseif (!empty($result['error'])) {
            $this->logger->error(
                sprintf(
                    'Task %d aborted with error: %s (reason: %s), caused by %s (reason: %s).',
                    $taskId,
                    $result['error']['type'],
                    $result['error']['reason'],
                    $result['error']['caused_by']['type'],
                    $result['error']['caused_by']['reason']
                )
            );
        } else {
            $this->logger->notice(
                sprintf(
                    'Successfully completed task %d (created: %d, updated: %d, deleted: %d)',
                    $taskId,
                    $result['task']['status']['created'],
                    $result['task']['status']['updated'],
                    $result['task']['status']['deleted']
                )
            );
        }
    }
}
