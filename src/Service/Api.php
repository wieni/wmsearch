<?php

namespace Drupal\wmsearch\Service;

use Drupal\Core\Extension\ModuleHandlerInterface;

class Api extends BaseApi
{
    protected $cwd;

    protected $baseMapping;

    public function __construct(
        $appRoot,
        ModuleHandlerInterface $mh,
        $ep,
        $index,
        $timeout = 10.0
    ) {
        parent::__construct($ep, $index, $timeout);
        $this->cwd = $appRoot .
            DIRECTORY_SEPARATOR .
            $mh->getModule('wmsearch')->getPath();
    }

    public function createIndex($shards = 1, $replicas = 0)
    {
        $m = $this->getBaseMapping();
        $m['settings']['index.number_of_shards'] = $shards;
        $m['settings']['index.number_of_replicas'] = $replicas;

        $this->put($this->index, $m);
    }

    public function deleteIndex()
    {
        $this->delete($this->index);
    }

    protected function getBaseMapping()
    {
        if (isset($this->baseMapping)) {
            return $this->baseMapping;
        }

        $m = json_decode(
            file_get_contents(
                $this->cwd .
                DIRECTORY_SEPARATOR .
                'assets' .
                DIRECTORY_SEPARATOR .
                'mapping.json'
            ),
            true
        );

        if (!$m) {
            throw new \RuntimeException('Could not fetch base mapping');
        }

        return $this->baseMapping = $m;
    }
}

