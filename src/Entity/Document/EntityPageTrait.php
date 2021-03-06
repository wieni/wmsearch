<?php

namespace Drupal\wmsearch\Entity\Document;

/**
 * Convenience trait for implementors of Drupal\Core\Entity\EntityInterface.
 */
trait EntityPageTrait
{
    /**
     * Return the document types this entity supports.
     *
     * @return string[]
     */
    public function getElasticTypes()
    {
        return ['page'];
    }

    /**
     * Returns a DocumentCollectionInterface instance or service name that
     * manages the documents of the given document type for this instance.
     *
     * @param string $type The document type.
     *
     * @return \Drupal\wmsearch\Entity\Document\DocumentCollectionInterface|string
     */
    public function getElasticDocumentCollection($type)
    {
        $collection = new EntityDocumentCollection();
        $collection->setEntity($this);
        $collection->setDocumentType($type);

        return $collection;
    }
}

