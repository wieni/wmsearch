<?php

namespace Drupal\wmsearch\Service;

use Drupal\wmsearch\Entity\Query\PageQuery;

class QueryBuilder implements QueryBuilderInterface
{
    protected $highlightAmount = 1;
    protected $highlightSize = 120;

    public function build($query, $page, $perPage)
    {
        return (new PageQuery())
            ->from($perPage * $page)
            ->size($perPage)
            ->setHighlight(
                $this->highlightAmount,
                $this->highlightSize,
                ['title', 'body'],
                '<em>',
                '</em>'
            )
            ->addMultiMatch($query, ['title', 'body']);
    }
}

