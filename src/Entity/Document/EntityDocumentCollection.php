<?php

namespace Drupal\wmsearch\Entity\Document;

class EntityDocumentCollection implements EntityDocumentCollectionInterface
{
    /** @var ElasticEntityInterface */
    protected $entity;
    /** @var string */
    protected $docType;

    public function setEntity(ElasticEntityInterface $entity)
    {
        $this->entity = $entity;
    }

    public function setDocumentType($type)
    {
        $this->docType = $type;
    }

    /**
     * A unique collection name. Used to track and cleanup the index.
     *
     * @example node_fr_123_page
     *
     * @return string
     */
    public function getCollectionName()
    {
        return sprintf(
            '%s_%s_%s_%s',
            $this->entity->getEntityTypeId(),
            $this->entity->language()->getId(),
            $this->entity->id(),
            $this->docType
        );
    }

    /**
     * Returns all current document ids for this collection.
     *
     * @return string[]|int[]
     */
    public function getElasticIds()
    {
        return [sprintf('%s-%s', $this->docType, wmsearch_entity_id($this->entity))];
    }

    /**
     * Returns elastic array that must satisfy the mapping rules of the
     * document type.
     *
     * @param string $elasticId
     *
     * @return array
     */
    public function toElasticArray($elasticId)
    {
        return [
            'id' => $this->entity->id(), // this isn't the elasticId
            'type' => $this->entity->getEntityTypeId(), // this isn't the document type
            'bundle' => $this->entity->bundle(),
            'url' => $this->entity->toUrl()->toString(),
            'language' => $this->entity->language()->getId(),
        ];
    }
}
