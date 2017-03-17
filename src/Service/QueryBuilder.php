<?php

namespace Drupal\wmsearch\Service;

use Drupal\wmsearch\Entity\Query\PageQuery;

class QueryBuilder implements QueryBuilderInterface
{
    protected $highlightAmount = 1;
    protected $highlightSize = 120;

    protected $fields = ['title', 'intro', 'body', 'terms'];
    protected $highlights = ['title', 'intro', 'body'];

    public function build($query, $offset, $amount)
    {
        // return (new PageQuery())
        //     ->setSource('title')
        //     ->complete($query);

        return (new PageQuery())
            ->from($offset)
            ->size($amount)
            ->setHighlight(
                $this->highlightAmount,
                $this->highlightSize,
                $this->highlights,
                '<em>',
                '</em>'
            )
            ->addMultiMatch($query, $this->fields);
    }
}

