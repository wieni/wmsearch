<?php

namespace Drupal\wmsearch\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
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

