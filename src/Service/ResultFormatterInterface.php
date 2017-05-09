<?php

namespace Drupal\wmsearch\Service;

use Drupal\wmsearch\Entity\Result\SearchResult;
use Drupal\wmsearch\Exception\ApiException;

interface ResultFormatterInterface
{
    /**
     * @return array
     */
    public function format(
        SearchResult $result,
        $highlightPreTag,
        $highlightPostTag
    );

    public function formatException(ApiException $e = null);
}

