<?php

namespace Drupal\wmsearch\Service;

use Drupal\wmsearch\Entity\Document\DocumentInterface;
use Drupal\wmsearch\Entity\Query\QueryInterface;
use Drupal\wmsearch\Entity\Query\HighlightInterface;
use Drupal\wmsearch\Entity\Result\SearchResult;
use Drupal\wmsearch\Exception\ApiException;
use Drupal\wmsearch\Exception\NotIndexableException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\PumpStream;

class BaseApi
{
    /** @var Client */
    protected $client;
    protected $ep;
    protected $index;
    protected $timeout;

    public function __construct($ep, $index, $timeout = 10.0)
    {
        if (empty($index)) {
            throw new \InvalidArgumentException(
                'The elastic index name cannot be empty'
            );
        }

        $this->ep = $ep;
        $this->index = $index;
        $this->client = new Client();
        $this->timeout = $timeout;
    }

    public function addDoc(DocumentInterface $doc)
    {
        foreach ($doc->getElasticTypes() as $type) {
            try {
                $arr = $doc->toElasticArray($type);
                $this->put(
                    sprintf(
                        '%s/%s/%s',
                        $this->index,
                        $type,
                        $doc->getElasticId()
                    ),
                    $arr
                );
            } catch (NotIndexableException $e) {
                $this->delDoc($type, $doc->getElasticId());
            }
        }
    }

    /**
     * @param DocumentInterface[] $docs List of documents to update
     *                                  using the _bulk api.
     */
    public function addDocs(array $docs)
    {
        $_docs = [];
        foreach ($docs as $i => $doc) {
            if (!($doc instanceof DocumentInterface)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Document at index `%s` of type `%s` does not implement DocumentInterface',
                        $i,
                        get_class($doc)
                    )
                );
            }

            foreach ($doc->getElasticTypes() as $type) {
                try {
                    $_docs[] = [
                        'type' => $type,
                        'id' => $doc->getElasticId(),
                        'arr' => $doc->toElasticArray($type),
                    ];
                } catch (NotIndexableException $e) {
                    $this->delDoc($type, $doc->getElasticId());
                }
            }
        }

        $i = 0;
        $stream = new PumpStream(
            function () use (&$i, $_docs) {
                if (!isset($_docs[$i])) {
                    return false;
                }

                $doc = $_docs[$i];
                $i++;

                return json_encode(
                    [
                        'index' => [
                            '_type' => $doc['type'],
                            '_id' => $doc['id'],
                        ],
                    ]
                ) .
                "\n" .
                json_encode($doc['arr']) .
                "\n";
            }
        );

        $this->exec(
            sprintf('%s/_bulk', $this->index),
            'PUT',
            ['body' => $stream]
        );
    }

    public function getDoc($docType, $id)
    {
        return $this->get(sprintf('%s/%s/%s', $this->index, $docType, $id));
    }

    public function delDoc($docType, $id)
    {
        try {
            return $this->delete(sprintf('%s/%s/%s', $this->index, $docType, $id));
        } catch (ApiException $e) {
            if (!$e->isNotFound()) {
                throw $e;
            }
        }

        return $this;
    }

    /**
     * @return SearchResult
     */
    public function search(QueryInterface $query)
    {
        return new SearchResult($this->execQuery($query));
    }

    /**
     * @return SearchResult
     */
    public function highlightSearch(HighlightInterface $query)
    {
        if (!$query->hasHighlight()) {
            return $this->search($query);
        }

        $newAffixes = ['#[elastic-highlight]#', "#[elastic-highlight-end]#'"];
        $affixes = [
            $query->getHighlightPreTag(),
            $query->getHighlightPostTag(),
        ];

        $query->setHighlight(
            $query->getHighlightAmount(),
            $query->getHighlightSize(),
            $query->getHighlightFields(),
            $newAffixes[0],
            $newAffixes[1]
        );

        $results = $this->execQuery($query);

        if (empty($results['hits']['hits'])) {
            return new SearchResult($results);
        }

        $stripper = new HtmlStripper();
        foreach ($results['hits']['hits'] as &$result) {
            if (empty($result['highlight'])) {
                continue;
            }

            foreach ($result['highlight'] as &$hs) {
                foreach ($hs as &$h) {
                    $h = str_replace(
                        $newAffixes,
                        $affixes,
                        $stripper->strip($h)
                    );
                }
            }
        }

        return new SearchResult($results);
    }

    /**
     * Simple http health check
     */
    public function health()
    {
        $this->get($this->index);
    }

    /**
     * Makes sure every document added up until now is searchable.
     */
    public function refresh()
    {
        return $this->post(sprintf('%s/_refresh', $this->index));
    }

    /**
     * Make sure data is commited to disk. (slow)
     *
     * Lucene commit. i.e.: fsync
     */
    public function flush()
    {
        return $this->post(
            sprintf('%s/_flush', $this->index),
            ['wait_if_ongoing' => true]
        );
    }

    protected function execQuery(QueryInterface $query)
    {
        $docType = $query->getDocumentType();
        if (!$docType) {
            throw new \InvalidArgumentException(
                'Query doesn\'t specify a document type'
            );
        }

        return $this->post(
            sprintf(
                '%s/%s/_search',
                $this->index,
                $query->getDocumentType()
            ),
            $query->toArray()
        );
    }

    protected function get($ep, array $options = [])
    {
        return $this->exec($ep, 'GET', $options);
    }

    protected function put($ep, array $data = [])
    {
        return $this->exec($ep, 'PUT', ['body' => json_encode($data)]);
    }

    protected function post($ep, array $data = [])
    {
        return $this->exec($ep, 'POST', ['body' => json_encode($data)]);
    }

    protected function delete($ep)
    {
        return $this->exec($ep, 'DELETE');
    }

    protected function exec($ep, $method, array $options = [])
    {
        try {
            $r = $this->client->request(
                $method,
                sprintf('%s/%s', $this->ep, $ep),
                $options + ['timeout' => $this->timeout]
            );
        } catch (\GuzzleHttp\Exception\ClientException $e) {
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
