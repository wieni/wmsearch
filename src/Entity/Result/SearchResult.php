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

