<?php

namespace Drupal\wmsearch\Service\Api;

class AliasApi extends BaseApi
{
    public function aliasExists(string $alias)
    {
        foreach ($this->getAliases() as $index => $info) {
            if (isset($info['aliases'][$alias])) {
                return true;
            }
        }

        return false;
    }

    public function getAliases(string $index = null)
    {
        if ($index) {
            return $this->get(sprintf('%s/_alias/*', $index));
        }

        return $this->get(sprintf('_alias/*'));
    }

    public function getIndexName(string $alias)
    {
        $aliases = $this->getAliases($alias);
        return array_keys($aliases)[0] ?? null;
    }

    public function addAliases(array $aliases)
    {
        $actions = [];

        foreach ($aliases as $index => $alias) {
            $actions[] = ['add' => compact('index', 'alias')];
        }

        $this->post('_aliases', compact('actions'));
    }

    public function addAlias(string $index, string $alias)
    {
        $this->addAliases([$index => $alias]);
    }

    public function removeAliases(array $aliases)
    {
        $actions = [];

        foreach ($aliases as $index => $alias) {
            $actions[] = ['remove' => compact('index', 'alias')];
        }

        $this->post('_aliases', compact('actions'));
    }

    public function removeAlias(string $index, string $alias)
    {
        $this->removeAliases([$index => $alias]);
    }
}
