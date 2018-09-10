<?php

namespace Drupal\wmsearch\Entity\Document;

class VerbatimPage extends AbstractPage
{
    protected $id;
    protected $data;

    public function __construct(
        $id,
        $title,
        array $body = []
    ) {
        $this->id = $id;

        $this->data = [
            'title' => $title,
            'body' => $body,
        ];
    }

    public function setIntro($intro)
    {
        return $this->set('intro', $intro);
    }

    public function setChanged($changed)
    {
        return $this->set('changed', $changed);
    }

    public function setCreated($created)
    {
        return $this->set('created', $created);
    }

    public function setTerms(array $terms)
    {
        return $this->set('terms', $terms);
    }

    public function setLanguage($language)
    {
        return $this->set('language', $language);
    }

    public function setType($type)
    {
        return $this->set('type', $type);
    }

    public function setBundle($bundle)
    {
        return $this->set('bundle', $bundle);
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function getElasticId($type)
    {
        return sprintf('%s-%s', $type, $this->id);
    }

    public function toElasticArray($type)
    {
        return $this->data;
    }
}

