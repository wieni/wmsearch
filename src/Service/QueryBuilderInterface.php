<?php

namespace Drupal\wmsearch\Service;

interface QueryBuilderInterface
{
    /**
     * @param string $query   The search query
     * @param int    $offset  Offset of the first item
     * @param int    $amount  Amount of items
     */
    public function build($query, $offset, $amount);
}

