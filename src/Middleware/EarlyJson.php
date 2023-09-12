<?php

namespace Drupal\wmsearch\Middleware;

use Drupal\wmsearch\Entity\Query\HighlightInterface;
use Drupal\wmsearch\Exception\ApiException;
use Drupal\wmsearch\Service\Api\SearchApi;
use Drupal\wmsearch\Service\ResultFormatterInterface;
use Drupal\wmsearch\Service\QueryBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class EarlyJson implements HttpKernelInterface
{
    /** @var HttpKernelInterface */
    protected $next;
    /** @var SearchApi */
    protected $searchApi;
    /** @var ResultFormatterInterface */
    protected $formatter;
    /** @var QueryBuilderInterface */
    protected $builder;

    protected $path;

    public function __construct(
        HttpKernelInterface $next,
        SearchApi $searchApi,
        ResultFormatterInterface $formatter,
        QueryBuilderInterface $builder,
        $path
    ) {
        $this->next = $next;
        $this->searchApi = $searchApi;
        $this->builder = $builder;
        $this->formatter = $formatter;
        $this->path = $path;
    }

    public function handle(
        Request $request,
        $type = self::MAIN_REQUEST,
        $catch = true
    ): \Symfony\Component\HttpFoundation\Response
    {
        if ($request->getPathInfo() !== $this->path) {
            return $this->next->handle($request, $type, $catch);
        }

        $query = $request->query->get('q');
        $amount = min(100, (int) $request->query->get('a', 5));
        $offset = (int) $request->query->get('o', 0);
        $e = null;

        try {
            if (empty($query)) {
                throw new ApiException('No query provided');
            }

            $q = $this->builder->build($query, $offset, $amount);
            $pre = $post = '';
            if ($q instanceof HighlightInterface) {
                $pre = $q->getHighlightPreTag();
                $post = $q->getHighlightPostTag();
            }
            return new JsonResponse(
                $this->formatter->format(
                    $this->searchApi->highlightSearch($q),
                    $pre,
                    $post
                )
            );
        } catch (ApiException $e) {
        }

        return new JsonResponse($this->formatter->formatException($e));
    }
}

