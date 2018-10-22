<?php

namespace Drupal\wmsearch\Service\Api;

class ReindexApi extends BaseApi
{
    /**
     * @param string $sourceIndex
     * @param string $destIndex
     * @param array $types
     */
    public function reindex($sourceIndex, $destIndex, array $types = ['page'])
    {
        $result = $this->post('_reindex?wait_for_completion=false', [
            'source' => [
                'index' => $sourceIndex,
                'query' => [
                    'bool' => [
                        'should' => array_map(
                            function ($type) { return ['type' => ['value' => $type]]; },
                            $types
                        ),
                    ],
                ],
            ],
            'dest' => [
                'index' => $destIndex,
                'version_type' => 'internal',
            ],
        ]);

        return $result['task'];
    }
}
