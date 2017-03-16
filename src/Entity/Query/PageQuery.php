<?php

namespace Drupal\wmsearch\Entity\Query;

use Drupal\Core\Language\LanguageInterface;

class PageQuery extends Query
{
    public function __construct(array $query = [])
    {
        parent::__construct('page', $query);
    }

    public function addCompletion($query, $fuzzy = 2)
    {
        parent::addCompletion('suggest', $query, $fuzzy);

        return $this;
    }

    public function filterLanguage($lang)
    {
        if ($lang instanceof LanguageInterface) {
            $lang = $lang->getId();
        }

        return $this->add('query', 'bool', 'filter', ['term' => ['language' => $lang]]);
    }

    public function filterType($type)
    {
        return $this->add('query', 'bool', 'filter', ['term' => ['type' => $type]]);
    }

    public function filterBundle($bundle)
    {
        return $this->add('query', 'bool', 'filter', ['term' => ['type' => $bundle]]);
    }

    public function filterTerm($term)
    {
        return $this->add('query', 'bool', 'filter', ['term' => ['terms' => $term]]);
    }

    public function filterTerms(array $terms)
    {
        return $this->add('query', 'bool', 'filter', ['terms' => ['terms' => $terms]]);
    }

    /**
     * @param int $start Unix ts
     * @param int $end Unix ts
     */
    public function filterRangeCreated($start = null, $end = null)
    {
        return $this->filterRange('created', $start, $end);
    }

    /**
     * @param int $start Unix ts
     * @param int $end Unix ts
     */
    public function filterRangeChanged($start = null, $end = null)
    {
        return $this->filterRange('changed', $start, $end);
    }

    protected function filterRange($field, $start = null, $end = null)
    {
        $d = [];

        if (isset($start)) {
            $d['range'][$field]['gte'] = $start;
        }

        if (isset($end)) {
            $d['range'][$field]['lt'] = $end;
        }

        if (!empty($d)) {
            $this->add('query', 'bool', 'filter', $d);
        }

        return $this;
    }
}

