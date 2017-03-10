<?php

namespace Drupal\wmsearch\Controller;

use Drupal\wmsearch\Service\Api;
use Drupal\wmsearch\Exception\ApiException;
use Drupal\wmsearch\Entity\Document\VerbatimPage;
use Drupal\wmsearch\Entity\Query\Query;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class SearchController extends ControllerBase
{
    /** @var Api */
    protected $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('wmsearch.api')
        );
    }

    public function health()
    {
        try {
            $this->api->health();
            return new JsonResponse(['msg' => 'ok']);
        } catch (ApiException $e) {
        }

        return new JsonResponse(
            ['err' => 'not ok'],
            JsonResponse::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}

