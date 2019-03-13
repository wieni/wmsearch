<?php

namespace Drupal\wmsearch\Service;

use Drupal\wmsearch\Entity\Query\PageQuery;

class QueryBuilder implements QueryBuilderInterface
{
    protected $highlightAmount = 1;
    protected $highlightSize = 120;

    protected $fields = ['title', 'intro', 'body', 'terms'];
    protected $highlights = ['title', 'intro', 'body'];

    protected $operator = 'or';
    protected $minimumShouldMatch = null;

    public function build($query, $offset, $amount)
    {
        // return (new PageQuery())
        //     ->setSource('title')
        //     ->complete($query);

        $query = (new PageQuery())
            ->from($offset)
            ->size($amount)
            ->setHighlight(
                $this->highlightAmount,
                $this->highlightSize,
                $this->highlights,
                '<em>',
                '</em>'
            );

        $decay = \Drupal::state()->get('wmsearch.decay', []);

        if ($decay['enabled'] ?? false) {
            $query->setDecayFunction(
                $decay['field'] ?? 'created',
                time(),
                $decay['scale'] ?? '5d',
                (float) ($decay['decay'] ?? 0.5),
                $decay['offset'] ?? '0d',
                $decay['function'] ?? 'exp'
            );
        }

        $query->addMultiMatch($query, $this->fields, $this->operator, $this->minimumShouldMatch);

        return $query;
    }
}

