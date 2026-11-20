<?php

namespace Drupal\wmsearch\Service\Api;

use Drupal\wmsearch\Exception\ApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class BaseApi
{
    /** @var Client */
    protected $client;
    /** @var string */
    protected $endpoint;

    public function __construct($endpoint, Client $client)
    {
        $this->endpoint = $endpoint;
        $this->client = $client;
    }

    protected function get($endpoint, array $options = [])
    {
        return $this->exec($endpoint, 'GET', $options);
    }

    // The (string) casts keep sending an empty body when json_encode() fails:
    // guzzlehttp/psr7 3 (Guzzle 8) throws on a bool body where 2 sent ''.
    protected function put($endpoint, array $data = [])
    {
        return $this->exec($endpoint, 'PUT', ['body' => (string) json_encode($data)]);
    }

    protected function post($endpoint, array $data = [])
    {
        return $this->exec($endpoint, 'POST', ['body' => (string) json_encode($data)]);
    }

    protected function delete($endpoint)
    {
        return $this->exec($endpoint, 'DELETE');
    }

    protected function exec($endpoint, $method, array $options = [])
    {
        try {
            $response = $this->client->request(
                $method,
                sprintf('%s/%s', $this->endpoint, $endpoint),
                $options
            );
        } catch (RequestException $e) {
            $body = null;

            // Guzzle 8 dropped hasResponse() and only has getResponse() on
            // its ResponseException subclass; Guzzle 6/7 return ?Response.
            $response = method_exists($e, 'getResponse') ? $e->getResponse() : null;
            if ($response) {
                $body = (string) $response->getBody();
            }

            throw new ApiException('Elastic api request failed', $body, 0, $e);
        }

        $body = json_decode($response->getBody(), true);
        if ($body === false) {
            throw new ApiException('Failed to decode response body');
        }

        return $body;
    }
}
