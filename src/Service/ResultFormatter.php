<?php

namespace Drupal\wmsearch\Service;

use Drupal\wmsearch\Entity\Result\SearchResult;
use Drupal\wmsearch\Entity\Result\Hit;
use Drupal\wmsearch\Entity\Result\Suggestion;
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

        foreach ($result->getSuggestionFields() as $field) {
            foreach ($result->getSuggestions($field) as $hit) {
                $d['suggestions'][$field][] = $this->formatSuggestion($hit);
            }
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

    protected function formatSuggestion(Suggestion $suggestion)
    {
        return [
            'document' => $suggestion->getSource(),
            'suggestion' => $suggestion->getSuggestion(),
        ];
    }

    public function formatException(ApiException $e = null)
    {
        // TODO expose all errors or use a SafeToPrintException...?
        return ['err' => $e ? $e->getMessage() : 'Something went wrong'];
    }
}

