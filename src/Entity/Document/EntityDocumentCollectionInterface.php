<?php

namespace Drupal\wmsearch\Entity\Document;

interface EntityDocumentCollectionInterface extends DocumentCollectionInterface
{
    public function setEntity(ElasticEntityInterface $entity);

    public function setDocumentType($docType);
}
