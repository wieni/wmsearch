<?php

namespace Drupal\wmsearch\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\queue_ui\QueueUIBatch;
use Drupal\wmcustom\Form\FormBase;
use Drupal\wmsearch\Service\Api\AliasApi;
use Drupal\wmsearch\Service\Api\IndexApi;
use Drupal\wmsearch\Service\Api\StatsApi;
use Drupal\wmsearch\Service\Indexer;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OverviewForm extends FormBase
{
    /** @var string */
    protected $indexName;
    /** @var AliasApi */
    protected $aliasApi;
    /** @var StatsApi */
    protected $statsApi;
    /** @var Indexer */
    protected $indexer;
    /** @var QueueInterface */
    protected $queue;
    /** @var ModuleHandlerInterface */
    protected $moduleHandler;
    /** @var MessengerInterface */
    protected $messenger;

    public function __construct(
        string $indexName,
        AliasApi $aliasApi,
        StatsApi $statsApi,
        Indexer $indexer,
        QueueFactory $queueFactory,
        ModuleHandlerInterface $moduleHandler,
        MessengerInterface $messenger
    ) {
        $this->indexName = $indexName;
        $this->aliasApi = $aliasApi;
        $this->statsApi = $statsApi;
        $this->indexer = $indexer;
        $this->queue = $queueFactory->get('wmsearch.index');
        $this->moduleHandler = $moduleHandler;
        $this->messenger = $messenger;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->getParameter('wmsearch.elastic.index'),
            $container->get('wmsearch.api.alias'),
            $container->get('wmsearch.api.stats'),
            $container->get('wmsearch.indexer'),
            $container->get('queue'),
            $container->get('module_handler'),
            $container->get('messenger')
        );
    }

    public function getFormId()
    {
        return 'wmsearch_overview';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['tabs'] = [
            '#type' => 'vertical_tabs',
        ];

        $form['index'] = [
            '#type' => 'details',
            '#group' => 'tabs',
            '#title' => $this->t('Index'),
        ];

        $this->buildIndexForm($form);

        $form['queue'] = [
            '#type' => 'details',
            '#group' => 'tabs',
            '#title' => $this->t('Queue'),
        ];

        $this->buildQueueForm($form);

        return $form;
    }

    protected function buildIndexForm(array &$form)
    {
        $stats = $this->statsApi->getStats($this->indexName);
        $indexName = $this->indexName;

        if ($alias = $this->aliasApi->getIndexName($indexName)) {
            list($alias, $indexName) = [$indexName, $alias];
        }

        $form['index']['index_name'] = [
            '#type' => 'item',
            '#title' => $this->t('Index name'),
            '#markup' => $indexName,
        ];

        $form['index']['active_alias'] = [
            '#type' => 'item',
            '#title' => $this->t('Active alias'),
            '#markup' => $alias,
        ];

        $form['index']['docs_total'] = [
            '#type' => 'item',
            '#title' => 'Total documents',
            '#markup' => $stats['total']['docs']['count'],
        ];

        $form['index']['docs_deleted'] = [
            '#type' => 'item',
            '#title' => 'Deleted documents',
            '#markup' => $stats['total']['docs']['deleted'],
        ];

        $form['index']['store_size'] = [
            '#type' => 'item',
            '#title' => 'Store size',
            '#markup' => format_size($stats['total']['store']['size_in_bytes']),
        ];

        $form['index']['actions']['index_recreate'] = [
            '#type' => 'submit',
            '#value' => 'Recreate index',
            '#action' => 'index_recreate',
        ];
    }

    protected function buildQueueForm(array &$form)
    {
        $form['queue']['queue_total'] = [
            '#type' => 'item',
            '#title' => 'Total items',
            '#markup' => $this->queue->numberOfItems(),
        ];

        $form['queue']['actions']['queue_fill'] = [
            '#type' => 'submit',
            '#value' => $this->t('Fill queue'),
            '#action' => 'queue_fill',
        ];

        $form['queue']['actions']['queue_run'] = [
            '#type' => 'submit',
            '#value' => $this->t('Run queue'),
            '#action' => 'queue_run',
            '#access' => $this->moduleHandler->moduleExists('queue_ui')
                && $this->queue->numberOfItems() > 0,
        ];

        $form['queue']['actions']['queue_empty'] = [
            '#type' => 'submit',
            '#value' => $this->t('Empty queue'),
            '#action' => 'queue_empty',
            '#access' => $this->queue->numberOfItems() > 0,
        ];
    }

    public function submitForm(array &$form, FormStateInterface $formState)
    {
        $action = $formState->getTriggeringElement()['#action'];

        switch ($action) {
            case 'queue_run':
                $this->runQueue();
                break;
            case 'queue_fill':
                $this->fillQueue();
                break;
            case 'queue_empty':
                $this->emptyQueue();
                break;
            case 'index_recreate':
                $this->recreateIndex();
                break;
        }
    }

    protected function runQueue()
    {
        $batch = [
            'title' => t('Adding documents to index'),
            'operations' => [],
            'finished' => [QueueUIBatch::class, 'finish'],
        ];

        $batch['operations'][] = [QueueUIBatch::class . '::step', ['wmsearch.index']];

        batch_set($batch);
    }

    protected function fillQueue()
    {
        $this->indexer->queueAll(0, 0, 0);

        $this->messenger->addStatus(
            $this->t('Successfully filled queue with content')
        );
    }

    protected function emptyQueue()
    {
        $this->queue->deleteQueue();
        $this->queue->createQueue();

        $this->messenger->addStatus(
            $this->t('Successfully emptied queue.')
        );
    }

    protected function recreateIndex()
    {
        $this->indexer->purge();

        $this->messenger->addStatus(
            $this->t('Successfully recreated index %indexName', ['%indexName' => $this->indexName])
        );
    }
}
