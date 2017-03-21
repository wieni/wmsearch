<?php

namespace Drupal\wmsearch\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class DisableSimpleSearchSubscriber extends RouteSubscriberBase
{
    protected $enable;

    public function __construct($enable)
    {
        $this->enable = $enable;
    }

    protected function alterRoutes(RouteCollection $collection)
    {
        if (!$this->enable) {
            $collection->remove('wmsearch.simple');
        }
    }
}

