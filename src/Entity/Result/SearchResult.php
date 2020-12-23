<?php

namespace Drupal\wmsearch\Entity\Result;

class SearchResult
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return Hit[]
     */
    public function getHits()
    {
        return array_map(
            function ($item) {
                return new Hit($item);
            },
            $this->get('hits', 'hits')
        );
    }

    public function getSuggestionFields()
    {
        $suggestions = $this->get('suggest');
        if (empty($suggestions)) {
            return [];
        }

        return array_keys($suggestions);
    }

    /**
     * @return Suggestion[];
     */
    public function getSuggestions($field)
    {
        $suggestions = $this->get('suggest', $field);
        if (empty($suggestions)) {
            return [];
        }

        $items = [];
        foreach ($suggestions as $raws) {
            foreach ($raws['options'] as $item) {
                $items[] = new Suggestion($item);
            }
        }

        return $items;
    }

    public function getTotal()
    {
        return $this->get('hits', 'total');
    }

    public function getDuration()
    {
        return $this->get('took');
    }

    public function getMaxScore()
    {
        return $this->get('hits', 'max_score');
    }

    public function getAggregations()
    {
        if ($data = $this->get('aggregations')) {
            return $this->processAggregations($data);
        }

        return [];
    }

    public function getAggregation(...$args)
    {
        $aggregations = $this->get('aggregations', ...$args) ?? [];

        $items = [];
        foreach ($aggregations['buckets'] ?? [] as $item) {
            $items[$item['key']] = $item['doc_count'];
        }

        return $items;
    }

    /** @return int */
    public function getCount()
    {
        $count = $this->get('count');
        if (is_null($count)) {
            throw new \RuntimeException('::getCount can only be called on a count query');
        }

        return $count;
    }

    protected function processAggregations(array $aggregation, array $result = [], ?string $key = null): array
    {
        if (isset($key, $aggregation['buckets'])) {
            $result[$key] = [];

            foreach ($aggregation['buckets'] as $bucket) {
                /** @see Query::addReverseNestedAggregation */
                $result[$key][$bucket['key']] = $bucket["reverse_{$key}"]['doc_count']
                    ?? $bucket['doc_count'];
            }

            return $result;
        }

        foreach ($aggregation as $nestedKey => $nestedValue) {
            if (is_array($nestedValue)) {
                $result = $this->processAggregations($nestedValue, $result, $nestedKey);
            }
        }

        return $result;
    }

    protected function get()
    {
        $d = $this->data;
        $trail = func_get_args();
        foreach ($trail as $k) {
            if (!isset($d[$k])) {
                return null;
            }

            $d = $d[$k];
        }

        return $d;
    }
}

