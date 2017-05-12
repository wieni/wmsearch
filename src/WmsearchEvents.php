<?php

namespace Drupal\wmsearch;

final class WmsearchEvents
{
    /**
     * Will be triggered before a mapping is pushed to elastic.
     *
     * Allows you to alter or add to the mapping.
     *
     * The event object is an instance of
     * Drupal\wmsearch\Event\MappingEvent
     */
    const MAPPING = 'wmsearch.mapping';
}
