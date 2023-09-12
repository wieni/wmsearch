<?php

namespace Drupal\wmsearch\Event;

use Symfony\Contracts\EventDispatcher\Event;

class MappingEvent extends Event
{
    protected $mapping;

    public function __construct(array $mapping)
    {
        $this->mapping = $mapping;
    }

    public function getMapping()
    {
        return $this->mapping;
    }

    public function setMapping($mapping)
    {
        $this->mapping = $mapping;
    }
}
