<?php

namespace Drupal\wmsearch\Service;

use Drupal\wmsearch\Entity\Result\SearchResult;
use Drupal\wmsearch\Entity\Result\Hit;
use Drupal\wmsearch\Entity\Result\Suggestion;
use Drupal\wmsearch\Exception\ApiException;

class ResultFormatter implements ResultFormatterInterface
{
    public function format(SearchResult $result, $pre, $post)
    {
        $d = [
            'total' => $result->getTotal(),
            'results' => [],
        ];

        foreach ($result->getHits() as $hit) {
            $d['results'][] = $this->formatHit($hit, $pre, $post);
        }

        foreach ($result->getSuggestionFields() as $field) {
            foreach ($result->getSuggestions($field) as $hit) {
                $d['suggestions'][$field][] = $this->formatSuggestion($hit);
            }
        }

        return $d;
    }

    protected function formatHit(Hit $hit, $pre, $post)
    {
        $highlights = [];

        foreach ($hit->getHighlights() as $field => $hls) {
            foreach ($hls as $hl) {
                $highlights[$field][] = $this->formatHighlight(
                    $hl,
                    $pre,
                    $post
                );
            }
        }

        return [
            'document' => $hit->getSource(),
            'highlights' => $highlights,
        ];
    }

    protected function formatSuggestion(Suggestion $suggestion)
    {
        return [
            'document' => $suggestion->getSource(),
            'suggestion' => $suggestion->getSuggestion(),
        ];
    }

    protected function formatHighlight($hl, $pre, $post)
    {
        $hl = ltrim(str_replace("\n", ' ', $hl), '. ');
        $split = explode($pre, $hl, 2);
        $before = count($split) === 2 ? $split[0] : '';

        $sentencesBefore = explode('. ', $before);
        if (count($sentencesBefore) > 1) {
            $split[0] = implode('. ', array_slice($sentencesBefore, 1));
        }

        $hl = trim(implode($pre, $split));

        if ($hl[strlen($hl) - 1] === '.') {
            return $hl;
        }

        $split = explode($post, $hl);
        $after = count($split) > 1 ? end($split) : '';

        $sentencesAfter = explode('. ', $after);
        if (count($sentencesAfter) > 1) {
            $split[count($split) - 1] =
                implode('. ', array_slice($sentencesAfter, 0, -1)) . '.';
        }

        return implode($post, $split);
    }

    public function formatException(ApiException $e = null)
    {
        // TODO expose all errors or use a SafeToPrintException...?
        return ['err' => $e ? $e->getMessage() : 'Something went wrong'];
    }
}

