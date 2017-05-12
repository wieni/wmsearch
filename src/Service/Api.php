<?php

namespace Drupal\wmsearch\Service;

use Drupal\wmsearch\WmsearchEvents;
use Drupal\wmsearch\Event\MappingEvent;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Api extends BaseApi
{
    protected $cwd;

    protected $baseMapping;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    public function __construct(
        $appRoot,
        EventDispatcherInterface $dispatcher,
        ModuleHandlerInterface $mh,
        $ep,
        $index,
        $timeout = 10.0
    ) {
        parent::__construct($ep, $index, $timeout);
        $this->cwd = $appRoot .
            DIRECTORY_SEPARATOR .
            $mh->getModule('wmsearch')->getPath();

        $this->dispatcher = $dispatcher;
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

        $ev = new MappingEvent($m);
        $this->dispatcher->dispatch(WmsearchEvents::MAPPING, $ev);

        return $this->baseMapping = $ev->getMapping();
    }
}
