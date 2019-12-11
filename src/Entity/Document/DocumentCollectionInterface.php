<?php

namespace Drupal\wmsearch\Entity\Document;

interface DocumentCollectionInterface
{
    /**
     * A unique collection name. Used to track and cleanup the index.
     *
     * @example node_fr_123_page
     *
     * @return string
     */
    public function getCollectionName();

    /**
     * Returns all current document ids for this collection.
     *
     * @return string[]|int[]
     */
    public function getElasticIds();

    /**
     * Returns elastic array that must satisfy the mapping rules of the
     * document type.
     *
     * @param string $elasticId
     *
     * @return array
     */
    public function toElasticArray($elasticId);
}
