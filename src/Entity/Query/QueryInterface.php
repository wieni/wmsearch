<?php

namespace Drupal\wmsearch\Entity\Query;

interface QueryInterface
{
    /**
     * Return the document type.
     *
     * @return string
     */
    public function getDocumentType();

    /**
     * Returns an array representing an elastic query body.
     *
     * @return array
     */
    public function toArray();

    /**
     * Returns true when the query is a count query.
     *
     * @return bool
     */
    public function isCount();
}

