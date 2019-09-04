<?php

namespace Drupal\wmsearch\Service\Api;

use Drupal\wmsearch\Exception\ApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class BaseApi
{
    /** @var Client */
    protected $client;
    /** @var string */
    protected $endpoint;

    public function __construct($endpoint, $timeout = 10.0)
    {
        $this->endpoint = $endpoint;
        $this->client = new Client([
            'verify' => false,
            'timeout' => $timeout,
            'headers' => [
                'content-type' => 'application/json',
                'Accept' => 'application/json'
            ],
        ]);
    }

    protected function get($endpoint, array $options = [])
    {
        return $this->exec($endpoint, 'GET', $options);
    }

    protected function put($endpoint, array $data = [])
    {
        return $this->exec($endpoint, 'PUT', ['body' => json_encode($data)]);
    }

    protected function post($endpoint, array $data = [])
    {
        return $this->exec($endpoint, 'POST', ['body' => json_encode($data)]);
    }

    protected function delete($endpoint)
    {
        return $this->exec($endpoint, 'DELETE');
    }

    protected function exec($endpoint, $method, array $options = [])
    {
        try {
            $r = $this->client->request(
                $method,
                sprintf('%s/%s', $this->endpoint, $endpoint),
                $options
            );
        } catch (ClientException $e) {
            throw new ApiException(
                'Elastic api request failed',
                (string) $e->getResponse()->getBody()
            );
        }

        $body = json_decode($r->getBody(), true);
        if ($body === false) {
            throw new ApiException(
                'Failed to decode response body'
            );
        }

        return $body;
    }
}
