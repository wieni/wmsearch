<?php

namespace Drupal\wmsearch\Service;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class IndexBatch
{
    protected const QUEUE_NAME = 'wmsearch.index';

    use DependencySerializationTrait;
    use StringTranslationTrait;

    /** @var MessengerInterface */
    protected $messenger;
    /** @var QueueWorkerManagerInterface */
    protected $workerManager;
    /** @var QueueFactory */
    protected $queueFactory;

    public function __construct(
        MessengerInterface $messenger,
        QueueWorkerManagerInterface $workerManager,
        QueueFactory $queueFactory
    ) {
        $this->messenger = $messenger;
        $this->workerManager = $workerManager;
        $this->queueFactory = $queueFactory;
    }

    public function run(): void
    {
        $batch = [
            'title' => $this->t('Adding documents to index'),
            'operations' => [[$this, 'doRun'], ['']],
            'finished' => [$this, 'finish'],
        ];

        batch_set($batch);
    }

    public function doRun(string $test, array &$context): void
    {
        // Make sure every queue exists. There is no harm in trying to recreate
        // an existing queue.
        $this->queueFactory->get(self::QUEUE_NAME)->createQueue();

        $worker = $this->workerManager->createInstance(self::QUEUE_NAME);
        $queue = $this->queueFactory->get(self::QUEUE_NAME);

        if (empty($context['results']['processed'])) {
            $context['results']['processed'] = 0;
        }

        $context['finished'] = 0;

        try {
            if ($item = $queue->claimItem()) {
                $context['message'] = $this->t('Adding documents to index: %count remaining.', [
                    '%count' => $queue->numberOfItems(),
                ]);

                // Process and delete item
                $worker->processItem($item->data);
                $queue->deleteItem($item);

                // Update context
                $context['results']['processed'][] = $item->item_id;
            }
            else {
                // If we cannot claim an item we must be done processing this queue.
                $context['finished'] = 1;
            }

        } catch (RequeueException $e) {
            // The worker requested the task be immediately requeued.
            $queue->releaseItem($item);

        } catch (SuspendQueueException $e) {
            // If the worker indicates there is a problem with the whole queue,
            // release the item and skip to the next queue.
            $queue->releaseItem($item);

            watchdog_exception('wmsearch', $e);
            $context['results']['errors'][] = $e->getMessage();

            // Marking the batch job as finished will stop further processing.
            $context['finished'] = 1;

        } catch (\Exception $e) {
            // In case of any other kind of exception, log it and leave the item
            // in the queue to be processed again later.
            watchdog_exception('wmsearch', $e);
            $context['results']['errors'][] = $e->getMessage();
        }
    }

    public function finish(bool $success, array $results): void
    {
        $this->messenger->addStatus(
            $this->formatPlural(
                count($results['processed']),
                'One document successfully indexed.',
                '@count documents successfully indexed.'
            )
        );

        if (!empty($results['errors'])) {
            $this->messenger->addStatus(
                $this->formatPlural(
                    count($results['errors']),
                    'An error occurred while indexing documents: @errors',
                    '@count errors occurred while indexing documents: <ul><li>@errors</li></ul>',
                    [
                        '@errors' => Markup::create(implode('</li><li>', $results['errors'])),
                    ]
                ),
                'error'
            );
        }
    }
}
