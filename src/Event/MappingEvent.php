<?php

namespace Drupal\wmsearch\Event;

use Symfony\Component\EventDispatcher\Event;

class MappingEvent extends Event
{
    protected $mapping;
    protected $index;

    public function __construct(array $mapping, $index)
    {
        $this->mapping = $mapping;
        $this->index = $index;
    }

    public function getMapping()
    {
        return $this->mapping;
    }

    public function setMapping($mapping)
    {
        $this->mapping = $mapping;
    }

    public function getIndex()
    {
        return $this->index;
    }
}
