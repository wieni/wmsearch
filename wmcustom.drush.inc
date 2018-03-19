<?php

function wmcustom_drush_command()
{
    $commands['wmsearch-queue'] = [
        'description' => 'Whimsical Queuing of all entities',
        'aliases' => ['wmsearch:queue', 'wmsq'],
        'options' => [
            'from' => 'Continue from last id',
            'limit' => 'Amount of items you have to process',
            'offset' => 'Amount of items you have to process',
        ],
    ];

    $commands['wmsearch-purge'] = [
        'description' => 'KILL IT WITH FIRE',
        'aliases' => ['wmsearch:purge', 'wmsp'],
    ];

    return $commands;
}

function drush_wmcustom_wmsearch_queue()
{
    $from = drush_get_option('from') ?: 0;
    $limit = drush_get_option('limit') ?: 0;
    $offset = drush_get_option('offset') ?: 0;

    \Drupal::service('wmsearch.indexer')->queueAll($from, $limit, $offset);
}

function drush_wmcustom_wmsearch_purge()
{
    \Drupal::service('wmsearch.indexer')->purge();
}
