<?php

namespace Drupal\wmsearch\Entity\Query;

class Query implements QueryInterface, HighlightInterface
{
    protected $query;
    protected $docType;

    public function __construct($docType, array $query = [])
    {
        $this->docType = $docType;
        $this->query = $query;
    }

    public function toArray()
    {
        return $this->query;
    }

    public function getDocumentType()
    {
        return $this->docType;
    }

    public function setDocumentType($docType)
    {
        $this->docType = $docType;

        return $this;
    }

    public function size($size)
    {
        return $this->set('size', (int) $size);
    }

    public function from($from)
    {
        return $this->set('from', (int) $from);
    }

    public function addMultiMatch($query, array $fields = [])
    {
        return $this
            ->set('query', 'multi_match', 'query', $query)
            ->set('query', 'multi_match', 'fields', $fields);
    }

    /**
     * Add a query
     *
     * @param string $type  The query type. e.g.: 'match'
     * @param array  $query The query. e.g.: ['title' => 'something']
     */
    public function addQuery($type, array $query)
    {
        return $this->set('query', $type, $query);
    }

    /**
     * Add a filter.
     *
     * @param string $type   The filter type. e.g.: 'range'
     * @param array  $filter The filter. e.g.: ['created' => ['lte' => 123123]]
     */
    public function addFilter($type, array $filter)
    {
        return $this->set('filter', $type, $filter);
    }

    public function setHighlight(
        $amount,
        $size,
        array $fields,
        $preTag = '<em>',
        $postTag = '</em>'
    ) {
        $_fields = [];
        foreach ($fields as $field) {
            $_fields[$field] = new \stdClass();
        }

        return $this
            ->set('highlight', 'number_of_fragments', $amount)
            ->set('highlight', 'fragment_size', $size)
            ->set('highlight', 'pre_tags', [$preTag])
            ->set('highlight', 'post_tags', [$postTag])
            ->set('highlight', 'fields', $_fields);
    }

    public function hasHighlight()
    {
        return isset($this->query['highlight']);
    }

    public function getHighlightAmount()
    {
        return $this->query['highlight']['number_of_fragments'] ?? 0;
    }

    public function getHighlightSize()
    {
        return $this->query['highlight']['fragment_size'] ?? 0;
    }

    public function getHighlightFields()
    {
        if (!isset($this->query['highlight']['fields'])) {
            return [];
        }

        return array_keys($this->query['highlight']['fields']);
    }

    public function getHighlightPreTag()
    {
        return $this->query['highlight']['pre_tags'][0] ?? '<em>';
    }

    public function getHighlightPostTag()
    {
        return $this->query['highlight']['post_tags'][0] ?? '<em>';
    }

    protected function set()
    {
        $keys = func_get_args();
        $value = array_pop($keys);
        $lk = array_pop($keys);

        $q = &$this->query;
        foreach ($keys as $k) {
            $q += [$k => []];
            $q = &$q[$k];
        }

        $q[$lk] = $value;

        return $this;
    }
}

