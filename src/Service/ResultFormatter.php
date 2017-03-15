<?php

namespace Drupal\wmsearch\Service;

use Drupal\wmsearch\Entity\Result\SearchResult;
use Drupal\wmsearch\Entity\Result\Hit;
use Drupal\wmsearch\Exception\ApiException;

class ResultFormatter implements ResultFormatterInterface
{
    public function format(SearchResult $result)
    {
        $d = [
            'total' => $result->getTotal(),
            'results' => [],
        ];

        foreach ($result->getHits() as $hit) {
            $d['results'][] = $this->formatHit($hit);
        }

        return $d;
    }

    protected function formatHit(Hit $hit)
    {
        return [
            'document' => $hit->getSource(),
            'highlights' => $hit->getHighlights(),
        ];
    }

    public function formatException(ApiException $e = null)
    {
        // TODO expose all errors or use a SafeToPrintException...?
        return ['err' => $e ? $e->getMessage() : 'Something went wrong'];
    }
}

