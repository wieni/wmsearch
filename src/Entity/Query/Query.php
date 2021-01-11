<?php

namespace Drupal\wmsearch\Entity\Query;

class Query implements QueryInterface, HighlightInterface
{
    protected $query;
    protected $docType;
    protected $isCount;

    public function __construct(array $query = [], $docType = '')
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

    public function addMultiMatch($query, array $fields = [], $operator = 'or', $minimumShouldMatch = null)
    {
        if ($this->has('query', 'function_score')) {
            if (!empty($minimumShouldMatch)) {
                $this->set('query', 'function_score', 'query', 'bool', 'must', 'multi_match', 'minimum_should_match', $minimumShouldMatch);
            }

            return $this
                ->set('query', 'function_score', 'query', 'bool', 'must', 'multi_match', 'query', $query)
                ->set('query', 'function_score', 'query', 'bool', 'must', 'multi_match', 'fields', $fields)
                ->set('query', 'function_score', 'query', 'bool', 'must', 'multi_match', 'operator', $operator);
        }

        if (!empty($minimumShouldMatch)) {
            $this->set('query', 'bool', 'must', 'multi_match', 'minimum_should_match', $minimumShouldMatch);
        }

        return $this
            ->set('query', 'bool', 'must', 'multi_match', 'query', $query)
            ->set('query', 'bool', 'must', 'multi_match', 'fields', $fields)
            ->set('query', 'bool', 'must', 'multi_match', 'operator', $operator);
    }

    public function addAggregation($name, $key, $size = 1000)
    {
        return $this
            ->set('aggs', $name, 'terms', 'field', $key)
            ->set('aggs', $name, 'terms', 'size', $size);
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-bucket-nested-aggregation.html
     */
    public function addNestedAggregation(string $path, string $name, string $key, $nestedName = null, int $size = 1000): self
    {
        return $this
            ->set('aggs', $name, 'nested', 'path', $path)
            ->set('aggs', $name, 'aggs', $nestedName ?? $name, 'terms', 'field', $key)
            ->set('aggs', $name, 'aggs', $nestedName ?? $name, 'terms', 'size', $size);
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-bucket-reverse-nested-aggregation.html
     */
    public function addReverseNestedAggregation(string $path, string $name, string $key, $nestedName = null, int $size = 1000): self
    {
        $nestedName = $nestedName ?? $name;
        $nestedReverseName = "reverse_{$nestedName}";
        $nestedReverseBucketsName = "reverse_{$nestedName}_buckets";

        return $this
            ->set('aggs', $name, 'nested', 'path', $path)
            ->set('aggs', $name, 'aggs', $nestedName, 'terms', 'field', $key)
            ->set('aggs', $name, 'aggs', $nestedName, 'terms', 'size', $size)
            ->set('aggs', $name, 'aggs', $nestedName, 'aggs', $nestedReverseName, 'reverse_nested', new \stdClass())
            ->set('aggs', $name, 'aggs', $nestedName, 'aggs', $nestedReverseName, 'aggs', $nestedReverseBucketsName, 'terms', 'field', $key)
            ->set('aggs', $name, 'aggs', $nestedName, 'aggs', $nestedReverseName, 'aggs', $nestedReverseBucketsName, 'terms', 'size', $size);
    }

    public function addFilteredReverseNestedAggregation(string $path, string $name, string $key, array $filter, $nestedName = null, int $size = 1000): self
    {
        $nestedName = $nestedName ?? $name;
        $nestedFilterName = "filtered_{$nestedName}";
        $nestedReverseName = "reverse_{$nestedName}";
        $nestedReverseBucketsName = "reverse_{$nestedName}_buckets";

        return $this
            ->set('aggs', $name, 'nested', 'path', $path)
            ->set('aggs', $name, 'aggs', $nestedFilterName, 'filter', $filter)
            ->set('aggs', $name, 'aggs', $nestedFilterName, 'aggs', $nestedName, 'terms', 'field', $key)
            ->set('aggs', $name, 'aggs', $nestedFilterName, 'aggs', $nestedName, 'terms', 'size', $size)
            ->set('aggs', $name, 'aggs', $nestedFilterName, 'aggs', $nestedName, 'aggs', $nestedReverseName, 'reverse_nested', new \stdClass())
            ->set('aggs', $name, 'aggs', $nestedFilterName, 'aggs', $nestedName, 'aggs', $nestedReverseName, 'aggs', $nestedReverseBucketsName, 'terms', 'field', $key)
            ->set('aggs', $name, 'aggs', $nestedFilterName, 'aggs', $nestedName, 'aggs', $nestedReverseName, 'aggs', $nestedReverseBucketsName, 'terms', 'size', $size);
    }

    public function setSource($source = '*')
    {
        return $this->set('_source', $source);
    }

    public function setSort(array $sort)
    {
        return $this->set('sort', $sort);
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

    public function addNotQuery($type, array $query)
    {
        if ($this->has('query', 'function_score')) {
            return $this->set('query', 'function_score', 'query', 'bool', 'must_not', [$type => $query]);
        }

        return $this->set('query', 'bool', 'must_not', [$type => $query]);
    }

    protected function ensureFunctionScore()
    {
        if (!$this->has('query', 'function_score') && $this->has('query')) {
            $q = $this->get('query');
            $this->del('query');
            $this->set('query', 'function_score', 'query', $q);
        }

        if (!$this->has('query', 'function_score', 'functions')) {
            $this->set('query', 'function_score', 'functions', []);
        }
    }

    public function setFunctionScoreBoostMode(string $value)
    {
        $this->ensureFunctionScore();

        return $this->set('query', 'function_score', 'boost_mode', $value);
    }

    public function setFunctionScoreScoreMode(string $value)
    {
        $this->ensureFunctionScore();

        return $this->set('query', 'function_score', 'score_mode', $value);
    }

    public function addFunctionScoreFunction(array $function)
    {
        $this->ensureFunctionScore();

        $functions = $this->get('query', 'function_score', 'functions');
        $functions[] = $function;

        return $this->set('query', 'function_score', 'functions', $functions);
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

        return $this->addFunctionScoreFunction([
            $type => [$field => $params],
        ]);
    }

    public function addShould(array $value)
    {
        if ($this->has('query', 'function_score')) {
            return $this->add('query', 'function_score', 'query', 'bool', 'should', $value);
        }

        return $this->add('query', 'bool', 'should', $value);
    }

    /**
     * The clause (query) must appear in matching documents and will contribute to the score.
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-bool-query.html
     */
    public function addMust(array $value): self
    {
        if ($this->has('query', 'function_score')) {
            return $this->add('query', 'function_score', 'query', 'bool', 'must', $value);
        }

        return $this->add('query', 'bool', 'must', $value);
    }

    /**
     * The clause (query) must not appear in the matching documents. Clauses are executed in filter
     * context meaning that scoring is ignored and clauses are considered for caching.
     * Because scoring is ignored, a score of 0 for all documents is returned.
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-bool-query.html
     */
    public function addMustNot(array $value): self
    {
        if ($this->has('query', 'function_score')) {
            return $this->add('query', 'function_score', 'query', 'bool', 'must_not', $value);
        }

        return $this->add('query', 'bool', 'must_not', $value);
    }

    /**
     * When you combine a should clause with a filter than all should clauses are optional,
     * so even documents that only match the filter will be returned.
     *
     * You can use the minimum_should_match parameter to specify the number or percentage of
     * should clauses returned documents must match. If the bool query includes at least one should clause
     * and no must or filter clauses, the default value is 1. Otherwise, the default value is 0.
     *
     * @see https://discuss.elastic.co/t/combine-should-with-filter-search-api/139129/2
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-bool-query.html#bool-min-should-match
     */
    public function setMinimumShouldMatch(?string $value): self
    {
        if ($this->has('query', 'function_score')) {
            return $value === null
                ? $this->del('query', 'function_score', 'query', 'bool', 'minimum_should_match')
                : $this->set('query', 'function_score', 'query', 'bool', 'minimum_should_match', (int) $value);
        }

        return $value === null
            ? $this->del('query', 'bool', 'minimum_should_match')
            : $this->set('query', 'bool', 'minimum_should_match', (int) $value);
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

    public function addNestedFilter(string $path, $type, array $filter): self
    {
        return $this->addNestedBool('filter', $path, $type, $filter);
    }

    public function addNestedQuery(string $path, $type, array $filter): self
    {
        return $this->addNestedBool('must', $path, $type, $filter);
    }

    public function addNestedNotQuery(string $path, $type, array $filter): self
    {
        return $this->addNestedBool('must_not', $path, $type, $filter);
    }

    protected function addNestedBool(string $occurrenceType, string $path, $type, array $filter): self
    {
        $filterPath = ['query', 'bool', $occurrenceType];
        if ($this->has('query', 'function_score')) {
            $filterPath = ['query', 'function_score', 'query', 'bool', $occurrenceType];
        }

        // Try to merge with an existing nested query with the same path first
        $keys = array_keys($this->get(...$filterPath) ?? []);
        foreach ($keys as $key) {
            if (
                $this->has(...$filterPath, ...[$key, 'nested'])
                && $this->get(...$filterPath, ...[$key, 'nested', 'path']) === $path
            ) {
                return $this->add(...$filterPath, ...[$key, 'nested', 'query', 'bool', $occurrenceType, [$type => $filter]]);
            }
        }

        // Add a new nested query
        return $this->add(...$filterPath, ...[[
            'nested' => [
                'path' => $path,
                'query' => [
                    'bool' => [
                        $occurrenceType => [
                            [$type => $filter],
                        ],
                    ],
                ],
            ],
        ]]);
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

    public function isCount($isCount = null)
    {
        if (is_null($isCount)) {
            return $this->isCount;
        }

        $this->isCount = (bool) $isCount;
        return $this;
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
