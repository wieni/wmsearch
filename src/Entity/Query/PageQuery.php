<?php

namespace Drupal\wmsearch\Entity\Query;

use Drupal\Core\Language\LanguageInterface;

class PageQuery extends Query
{
    public function __construct(array $query = [])
    {
        parent::__construct('page', $query);
    }

    public function complete($query, $fuzzy = 0)
    {
        return $this->addCompletion('suggest', $query, $fuzzy);
    }

    public function filterLanguages(array $langs)
    {
        return $this->addFilter(
            'terms',
            ['language' => $this->langIds($langs)]
        );
    }

    public function filterCompletionLanguages(array $langs)
    {
        return $this->addCompletionFilter(
            'suggest',
            'language',
            $this->langIds($langs)
        );
    }

    public function filterTypes(array $types)
    {
        return $this->addFilter('terms', ['type' => $types]);
    }

    public function filterCompletionTypes(array $types)
    {
        return $this->addCompletionFilter('suggest', 'type', $types);
    }

    public function filterBundles(array $bundles)
    {
        return $this->addFilter('terms', ['bundle' => $bundles]);
    }

    public function filterCompletionBundles(array $bundles)
    {
        return $this->addCompletionFilter('suggest', 'bundle', $bundles);
    }

    public function filterTerms(array $terms)
    {
        return $this->addFilter('terms', ['terms' => $terms]);
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

    /**
     * Decay the score of older items.
     *
     * @param string $halflife The difference between now and created at which
     *                         the score should be halved.
     *                         number + unit. e.g.: 60s, 60m, 24h or 31d
     * @param string $algo     The algorithm: one of 'exp', 'linear' or 'gauss'
     */
    public function decayCreated($halflife = '10d', $algo = 'exp')
    {
        return $this->decay('created', $halflife, $algo);
    }

    /**
     * Decay the score of older items.
     *
     * @param string $halflife The difference between now and created at which
     *                         the score should be halved.
     *                         number + unit. e.g.: 60s, 60m, 24h or 31d
     * @param string $algo     The algorithm: one of 'exp', 'linear' or 'gauss'
     */
    public function decayChanged($halflife = '10d', $algo = 'exp')
    {
        return $this->decay('changed', $halflife, $algo);
    }

    protected function decay($field, $halflife = '10d', $algo = 'exp')
    {
        return $this->setDecayFunction(
            $field,
            time(),
            $halflife,
            0.5,
            null,
            $algo
        );
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

    protected function langIds(array $langs)
    {
        return array_map(
            function ($lang) {
                if ($lang instanceof LanguageInterface) {
                    return $lang->getId();
                }

                return $lang;
            },
            $langs
        );
    }
}

