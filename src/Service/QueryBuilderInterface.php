<?php

namespace Drupal\wmsearch\Service;

interface QueryBuilderInterface
{
    /**
     * @param string $query   The search query
     * @param int    $page    The current page
     * @param int    $perPage Items per page
     */
    public function build($query, $page, $perPage);
}

