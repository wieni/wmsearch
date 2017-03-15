<?php

namespace Drupal\wmsearch\Entity\Document;

/**
 * Convenience trait for implementors of Drupal\Core\Entity\EntityInterface.
 */
trait EntityPageTrait /* implements Drupal\Core\Entity\EntityInterface */
{
    abstract public function id();

    abstract public function getEntityTypeId();

    abstract public function bundle();

    abstract public function toUrl($rel = 'canonical', array $options = []);

    abstract public function language();

    abstract public function toElasticArray($type);

    public static function getElasticTypes()
    {
        return ['page'];
    }

    final public function getElasticId()
    {
        $id = $this->id();
        if (!$id) {
            throw new \RuntimeException(
                'Can not retrieve the elastic id for an entity without id'
            );
        }

        return sprintf('%s:%s', $this->getEntityTypeId(), $id);
    }

    public function getBaseElasticArray()
    {
        return [
            'id' => $this->id(),
            'type' => $this->getEntityTypeId(),
            'bundle' => $this->bundle(),
            'url' => $this->toUrl()->toString(),
            'language' => $this->language()->getId(),
        ];
    }
}

