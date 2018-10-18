<?php

namespace Drupal\wmsearch\Exception;

class ApiException extends \RuntimeException
{
    protected $body;

    public function __construct(
        $msg,
        $body = null,
        $code = 0,
        \Throwable $previous = null
    ) {
        if (!$body) {
            parent::__construct($msg, $code, $previous);
            return;
        }

        $json = @json_decode($body, true);

        if (!$json) {
            parent::__construct(
                is_string($body) ? sprintf('%s: %s', $msg, $body) : $msg,
                $code,
                $previous
            );

            return;
        }

        $this->body = $json;

        parent::__construct(
            sprintf(
                '%s: %s',
                $msg,
                @json_encode($json, JSON_PRETTY_PRINT)
            ),
            $code,
            $previous
        );
    }

    public function getBody()
    {
        return $this->body;
    }

    public function is($type)
    {
        return is_array($this->body)
            && (
                (
                    isset($this->body['result'])
                    && $this->body['result'] === $type
                )
                || (
                    $type === 'not_found'
                    && isset($this->body['found'])
                    && $this->body['found'] === false
                )
                || (
                    isset($this->body['error']['root_cause'][0]['type'])
                    && $this->body['error']['root_cause'][0]['type'] === $type
                )
            );
    }

    public function isNotFound()
    {
        return $this->is('not_found');
    }

    public function isIndexExists()
    {
        return $this->is('index_already_exists_exception');
    }

    public function isIndexNotFound()
    {
        return $this->is('index_not_found_exception');
    }
}

