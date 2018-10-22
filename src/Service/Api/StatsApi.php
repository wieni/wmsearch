<?php

namespace Drupal\wmsearch\Service\Api;

class StatsApi extends BaseApi
{
    /** @var AliasApi */
    protected $aliasApi;

    public function __construct(
        $endpoint,
        AliasApi $aliasApi
    ) {
        parent::__construct($endpoint);
        $this->aliasApi = $aliasApi;
    }

    /** @param string|null $indexName */
    public function getStats($indexName = null)
    {
        if (!$indexName) {
            return $this->get('_stats');
        }

        if ($alias = $this->aliasApi->getIndexName($indexName)) {
            [$alias, $indexName] = [$indexName, $alias];
        }

        $response = $this->get(
            sprintf('%s/_stats', $indexName)
        );

        return $response['indices'][$indexName];
    }
}
