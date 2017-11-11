<?php

namespace Drupal\wmsearch\Entity\Document;

interface DocumentInterface
{
    /**
     * Return the document type.
     *
     * @return string[]
     */
    public static function getElasticTypes();

    /*
     * Returns the document/entity id.
     *
     * @param $type The document type.
     * @return string|int
     */
    public function getElasticId($type);

    /**
     * Returns an elastic document array that must satisfy the
     * mapping rules of the document type.
     *
     * @param string $type The document type.
     *
     * @return array
     */
    public function toElasticArray($type);
}

