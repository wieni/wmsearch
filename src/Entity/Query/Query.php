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
        if ($this->has('query', 'function_score')) {
            return $this
                ->set('query', 'function_score', 'query', 'bool', 'must', 'multi_match', 'query', $query)
                ->set('query', 'function_score', 'query', 'bool', 'must', 'multi_match', 'fields', $fields);
        }

        return $this
            ->set('query', 'bool', 'must', 'multi_match', 'query', $query)
            ->set('query', 'bool', 'must', 'multi_match', 'fields', $fields);
    }

    public function addAggregation($name, $key, $size = 1000)
    {
        return $this
            ->set('aggs', $name, 'terms', 'field', $key)
            ->set('aggs', $name, 'terms', 'size', $size);
    }

    public function setSource($source = '*')
    {
        return $this->set('_source', $source);
    }

    public function addCompletion($field, $query, $fuzzy = 2)
    {
        return $this
            ->set('suggest', $field, 'prefix', $query)
            ->set('suggest', $field, 'completion', 'field', $field)
            ->set('suggest', $field, 'completion', 'fuzzy', 'fuzziness', $fuzzy);
    }

    /**
     * Add a query
     *
     * @param string $type  The query type. e.g.: 'match'
     * @param array  $query The query. e.g.: ['title' => 'something']
     */
    public function addQuery($type, array $query)
    {
        if ($this->has('query', 'function_score')) {
            return $this->set('query', 'function_score', 'query', 'bool', 'must', [$type => $query]);
        }

        return $this->set('query', 'bool', 'must', [$type => $query]);
    }

    public function setFunctionScore(array $function)
    {
        if ($this->has('query') && !$this->has('query', 'function_score')) {
            $q = $this->get('query');
            $this->del('query');
            $function['query'] = $q;
        }

        return $this->set('query', 'function_score', $function);
    }

    public function setDecayFunction(
        $field,
        $origin,
        $scale,
        $decay = 0.5,
        $offset = null,
        $type = 'gauss'
    ) {
        $params = [
            'origin' => $origin,
            'scale' => $scale,
            'decay' => $decay,
        ];

        if ($offset) {
            $params['offset'] = $offset;
        }

        return $this->setFunctionScore([$type => [$field => $params]]);
    }

    /**
     * Add a filter.
     *
     * @param string $type   The filter type. e.g.: 'range'
     * @param array  $filter The filter. e.g.: ['created' => ['lte' => 123123]]
     */
    public function addFilter($type, array $filter)
    {
        if ($this->has('query', 'function_score')) {
            return $this->add('query', 'function_score', 'query', 'bool', 'filter', [$type => $filter]);
        }

        return $this->add('query', 'bool', 'filter', [$type => $filter]);
    }

    /**
     * Suggestion contexts i.e.: filters in suggestions.
     *
     * TODO
     * until https://github.com/elastic/elasticsearch/issues/21291#thread-subscription-status
     * is fixed these filters are OR'd instead of the more intuitive AND.
     */
    public function addCompletionFilter($field, $name, array $values)
    {
        return $this->set(
            'suggest',
            $field,
            'completion',
            'contexts',
            $name,
            $values
        );
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

    protected function get()
    {
        $q = $this->query;
        foreach (func_get_args() as $k) {
            if (!isset($q[$k])) {
                return null;
            }

            $q = $q[$k];
        }

        return $q;
    }

    protected function del()
    {
        $q = &$this->query;
        $keys = func_get_args();
        $lk = array_pop($keys);
        foreach ($keys as $k) {
            if (!isset($q[$k])) {
                return;
            }

            $q = &$q[$k];
        }

        unset($q[$lk]);
    }

    protected function has()
    {
        $q = $this->query;
        foreach (func_get_args() as $k) {
            if (!isset($q[$k])) {
                return false;
            }

            $q = $q[$k];
        }

        return true;
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

    protected function add()
    {
        $keys = func_get_args();
        $value = array_pop($keys);
        $lk = array_pop($keys);

        $q = &$this->query;
        foreach ($keys as $k) {
            $q += [$k => []];
            $q = &$q[$k];
        }

        $q[$lk][] = $value;

        return $this;
    }
}
