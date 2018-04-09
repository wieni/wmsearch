<?php

namespace Drupal\wmsearch\Commands;

use Drupal\wmsearch\Service\Indexer;
use Drush\Commands\DrushCommands;

class IndexCommands extends DrushCommands
{
    /** @var Indexer */
    protected $indexer;

    public function __construct(Indexer $indexer)
    {
        $this->indexer = $indexer;
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
     */
    public function queue($options = ['from' => '0', 'limit' => '0', 'offset' => '0'])
    {
        $this->indexer->queueAll($options['from'], $options['limit'], $options['offset']);
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
}
