<?php

namespace Drupal\wmsearch\Entity\Document;

abstract class AbstractPage implements DocumentInterface
{
    public static function getElasticTypes()
    {
        return ['page'];
    }
}

