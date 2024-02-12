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

    public function hasBody()
    {
        return null !== $this->body;
    }

    /**
     * @example Body of a failed API request:
     *  Elastic api request failed: {
     *    "error": {
     *      "root_cause": [
     *        {
     *          "type": "parsing_exception",
     *          "reason": "No value specified for terms query",
     *          "line": 1,
     *          "col": 79
     *        }
     *      ],
     *      "type": "x_content_parse_exception",
     *      "reason": "[1:79] [bool] failed to parse field [filter]",
     *      "caused_by": {
     *        "type": "parsing_exception",
     *        "reason": "No value specified for terms query",
     *        "line": 1,
     *        "col": 79
     *      }
     *    },
     *    "status": 400
     *  }
    */
    public function getBody()
    {
        return $this->body;
    }

    public function getReason()
    {
        if ($this->hasBody()) {
            $reason = $this->getBody()['error']['root_cause'][0]['reason']
                ?? $this->getBody()['error']['reason']
                ?? null
            ;
        }

        return $reason ?? $this->getMessage();
    }

    public function getStatus()
    {
        if ($this->hasBody()) {
            $status = $this->getBody()['status'] ?? null;
        }

        return $status ?? ($this->getCode() ?: 400);
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
        return $this->is('index_already_exists_exception')
            || $this->is('resource_already_exists_exception');
    }

    public function isIndexNotFound()
    {
        return $this->is('index_not_found_exception');
    }
}

