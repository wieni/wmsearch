<?php

namespace Drupal\wmsearch\Middleware;

use Drupal\wmsearch\Service\BaseApi;
use Drupal\wmsearch\Exception\ApiException;
use Drupal\wmsearch\Service\ResultFormatterInterface;
use Drupal\wmsearch\Service\QueryBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class EarlyJson implements HttpKernelInterface
{
    /** @var HttpKernelInterface */
    protected $next;

    /** @var BaseApi */
    protected $api;

    /** @var ResultFormatterInterface */
    protected $formatter;

    /** @var QueryBuilderInterface */
    protected $builder;

    protected $path;

    public function __construct(
        HttpKernelInterface $next,
        BaseApi $api,
        ResultFormatterInterface $formatter,
        QueryBuilderInterface $builder,
        $path
    ) {
        $this->next = $next;
        $this->api = $api;
        $this->builder = $builder;
        $this->formatter = $formatter;
        $this->path = $path;
    }

    public function handle(
        Request $request,
        $type = self::MASTER_REQUEST,
        $catch = true
    ) {
        if ($request->getPathInfo() !== $this->path) {
            return $this->next->handle($request, $type, $catch);
        }

        $query = $request->query->get('q');
        $perPage = min(100, (int) $request->query->get('pp', 5));
        $page = (int) $request->query->get('p', 0);
        $e = null;

        try {
            if (empty($query)) {
                throw new ApiException('No query provided');
            }

            return new JsonResponse(
                $this->formatter->format(
                    $this->api->highlightSearch(
                        $this->builder->build($query, $page, $perPage)
                    )
                )
            );
        } catch (ApiException $e) {
        }

        return new JsonResponse($this->formatter->formatException($e));
    }
}

