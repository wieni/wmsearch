<?php

namespace Drupal\wmsearch\Service\Api;

class ReindexApi extends BaseApi
{
    /**
     * @param string $sourceIndex
     * @param string $destIndex
     * @param array $types
     */
    public function reindex($sourceIndex, $destIndex, array $types = [])
    {
        $payload = [
            'source' => [
                'index' => $sourceIndex,
            ],
            'dest' => [
                'index' => $destIndex,
                'version_type' => 'internal',
            ],
        ];

        if ($types) {
            $payload['source']['query']['bool']['should'] = array_map(
                function ($type) { return ['type' => ['value' => $type]]; },
                $types
            );
        }

        $result = $this->post('_reindex?wait_for_completion=false', $payload);

        return $result['task'];
    }
}
