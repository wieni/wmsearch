<?php

namespace Drupal\wmsearch\Controller;

use Drupal\wmsearch\Exception\ApiException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\wmsearch\Service\Api\IndexApi;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class SearchController extends ControllerBase
{
    /** @var IndexApi */
    protected $indexApi;

    public function __construct(
        IndexApi $indexApi
    ) {
        $this->indexApi = $indexApi;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('wmsearch.api.index')
        );
    }

    public function health()
    {
        try {
            $this->indexApi->health();
            return new JsonResponse(['msg' => 'ok']);
        } catch (ApiException $e) {
        }

        return new JsonResponse(
            ['err' => 'not ok'],
            JsonResponse::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}

