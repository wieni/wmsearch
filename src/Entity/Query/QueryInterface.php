<?php

namespace Drupal\wmsearch\Entity\Query;

interface QueryInterface
{
    /**
     * Returns an array representing an elastic query body.
     *
     * @return array
     */
    public function toArray();
}

