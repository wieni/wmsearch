<?php

namespace Drupal\wmsearch\Service\Api;

use Drupal\wmsearch\Entity\Query\HighlightInterface;
use Drupal\wmsearch\Entity\Query\QueryInterface;
use Drupal\wmsearch\Entity\Result\SearchResult;
use Drupal\wmsearch\Service\HtmlStripper;

class SearchApi extends BaseApi
{
    /** @var string */
    protected $index;

    public function __construct(
        string $endpoint,
        string $index
    ) {
        parent::__construct($endpoint);

        if (empty($index)) {
            throw new \InvalidArgumentException(
                'The elastic index name cannot be empty'
            );
        }

        $this->index = $index;
    }

    public function search(QueryInterface $query): SearchResult
    {
        return new SearchResult($this->execQuery($query));
    }

    public function highlightSearch(HighlightInterface $query): SearchResult
    {
        if (!$query->hasHighlight()) {
            return $this->search($query);
        }

        $newAffixes = ['#[elastic-highlight]#', "#[elastic-highlight-end]#'"];
        $affixes = [
            $query->getHighlightPreTag(),
            $query->getHighlightPostTag(),
        ];

        $query->setHighlight(
            $query->getHighlightAmount(),
            $query->getHighlightSize(),
            $query->getHighlightFields(),
            $newAffixes[0],
            $newAffixes[1]
        );

        $results = $this->execQuery($query);

        if (empty($results['hits']['hits'])) {
            return new SearchResult($results);
        }

        $stripper = new HtmlStripper();
        foreach ($results['hits']['hits'] as &$result) {
            if (empty($result['highlight'])) {
                continue;
            }

            foreach ($result['highlight'] as &$hs) {
                foreach ($hs as &$h) {
                    $h = str_replace(
                        $newAffixes,
                        $affixes,
                        $stripper->strip($h)
                    );
                }
            }
        }

        return new SearchResult($results);
    }

    protected function execQuery(QueryInterface $query)
    {
        $ep = sprintf(
            '%s/%s',
            $this->index,
            $query->isCount() ? '_count' : '_search'
        );

        if ($docType = $query->getDocumentType()) {
            $ep = sprintf(
                '%s/%s/%s',
                $this->index,
                $docType,
                $query->isCount() ? '_count' : '_search'
            );
        }

        return $this->post(
            $ep,
            $query->toArray()
        );
    }
}
