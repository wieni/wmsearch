<?php

namespace Drupal\wmsearch\Entity\Document;

/**
 * Convenience trait for implementors of Drupal\Core\Entity\EntityInterface.
 * @mixin \Drupal\Core\Entity\EntityInterface
 */
trait EntityPageTrait
{
    /**
     * @return string|int|null
     */
    abstract public function id();

    /**
     * @return string
     */
    abstract public function getEntityTypeId();

    /**
     * @return string
     */
    abstract public function bundle();

    /**
     * @return \Drupal\Core\Url
     */
    abstract public function toUrl($rel = 'canonical', array $options = []);

    /**
     * @return \Drupal\Core\Language\LanguageInterface
     */
    abstract public function language();

    abstract public function toElasticArray($type);

    public static function getElasticTypes()
    {
        return ['page'];
    }

    public function getElasticId($type)
    {
        return sprintf('%s-%s', $type, wmsearch_entity_id($this));
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

