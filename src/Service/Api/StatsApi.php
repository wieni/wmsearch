<?php

namespace Drupal\wmsearch\Service\Api;

class StatsApi extends BaseApi
{
    /** @var AliasApi */
    protected $aliasApi;

    public function __construct(
        $endpoint,
        AliasApi $aliasApi,
        $timeout = 10.0
    ) {
        parent::__construct($endpoint, $timeout);
        $this->aliasApi = $aliasApi;
    }

    /** @param string|null $indexName */
    public function getStats($indexName = null)
    {
        if (!$indexName) {
            return $this->get('_stats');
        }

        if ($alias = $this->aliasApi->getIndexName($indexName)) {
            list($alias, $indexName) = [$indexName, $alias];
        }

        $response = $this->get(
            sprintf('%s/_stats', $indexName)
        );

        return $response['indices'][$indexName];
    }
}
