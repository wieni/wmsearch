<?php

namespace Drupal\wmsearch\Service;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\wmsearch\Entity\Document\DocumentCollectionInterface;
use Drupal\wmsearch\Entity\Document\ElasticEntityInterface;
use Drupal\wmsearch\Entity\Document\EntityDocumentCollectionInterface;

class DocumentCollectionManager
{
    /** @var \Drupal\Core\DependencyInjection\ClassResolverInterface */
    protected $resolver;
    /** @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface */
    protected $kvf;
    /** @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface */
    protected $kv;

    public function __construct(
        ClassResolverInterface $resolver,
        KeyValueFactoryInterface $kvf
    ) {
        $this->resolver = $resolver;
        $this->kvf = $kvf;
    }

    public function getDocumentCollection(ElasticEntityInterface $entity, string $docType)
    {
        $documentCollection = $entity->getElasticDocumentCollection($docType);
        if (is_string($documentCollection)) {
            $documentCollection = $this->resolver->getInstanceFromDefinition($documentCollection);
        }

        if ($documentCollection instanceof EntityDocumentCollectionInterface) {
            $documentCollection->setEntity($entity);
            $documentCollection->setDocumentType($docType);
        }

        return $documentCollection instanceof DocumentCollectionInterface
            ? $documentCollection
            : null;
    }

    public function getIndexedIds(DocumentCollectionInterface $collection)
    {
        return array_unique(
            array_merge(
                $this->kv()->get($collection->getCollectionName(), []),
                $collection->getElasticIds()
            )
        );
    }

    public function setIndexedIds(
        DocumentCollectionInterface $collection,
        array $indexedIds
    ) {
        $this->kv()->set($collection->getCollectionName(), $indexedIds);
    }

    public function resetIndexedIds(string $collection)
    {
        $this->kv()->delete($collection);
    }

    protected function kv()
    {
        if (isset($this->kv)) {
            return $this->kv;
        }

        return $this->kv = $this->kvf->get('wmsearch_collection_manager');
    }
}
