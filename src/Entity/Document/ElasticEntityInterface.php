<?php

namespace Drupal\wmsearch\Entity\Document;

/**
 * @mixin \Drupal\Core\Entity\EntityInterface
 */
interface ElasticEntityInterface
{
    /**
     * Return the document types this entity supports.
     *
     * @return string[]
     */
    public function getElasticTypes();

    /**
     * Returns a DocumentCollectionInterface instance or service name that
     * manages the documents of the given document type for this instance.
     *
     * @param string $type The document type.
     *
     * @return \Drupal\wmsearch\Entity\Document\DocumentCollectionInterface|string
     */
    public function getElasticDocumentCollection($type);
}

