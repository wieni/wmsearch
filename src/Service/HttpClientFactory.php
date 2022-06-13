<?php

namespace Drupal\wmsearch\Service;

use GuzzleHttp\Client;

class HttpClientFactory
{
    public function create(array $config = []): Client
    {
        $config = array_merge($this->defaultConfig(), $config);
        return new Client($config);
    }

    protected function defaultConfig(): array
    {
        return [
            'verify' => false,
            'timeout' => 10.0,
            'headers' => [
                'content-type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ];
    }
}
