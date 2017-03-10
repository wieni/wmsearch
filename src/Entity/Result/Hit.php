<?php

namespace Drupal\wmsearch\Entity\Result;

class Hit
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getId()
    {
        return $this->data['_id'];
    }

    /**
     * Get a source value by list of arguments
     *
     * e.g.: getSource('a', 'b', 'c') will return the value of _source.a.b.c
     *       or null if it doesn't exist.
     *
     * @return mixed
     */
    public function getSource()
    {
        $s = $this->data['_source'] ?? [];
        foreach (func_get_args() as $k) {
            if (!isset($s[$k])) {
                return null;
            }

            $s = $s[$k];
        }

        return $s;
    }

    /**
     * @return float
     */
    public function getScore()
    {
        return $this->data['_score'];
    }

    /**
     * @return array
     */
    public function getHighlights($field = '')
    {
        if (!isset($this->data['highlight'])) {
            return [];
        }

        if (!$field) {
            return $this->data['highlight'];
        }

        return $this->data['highlight'][$field] ?? [];
    }
}

