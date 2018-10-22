<?php

namespace Drupal\wmsearch\Service\Api;

class AliasApi extends BaseApi
{
    /** @param string $alias */
    public function aliasExists($alias)
    {
        foreach ($this->getAliases() as $index => $info) {
            if (isset($info['aliases'][$alias])) {
                return true;
            }
        }

        return false;
    }

    /** @param string|null $index */
    public function getAliases($index = null)
    {
        if ($index) {
            return $this->get(sprintf('%s/_alias/*', $index));
        }

        return $this->get(sprintf('_alias/*'));
    }

    /** @param string $alias */
    public function getIndexName($alias)
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

    /**
     * @param string $index
     * @param string $alias
     */
    public function addAlias($index, $alias)
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

    /**
     * @param string $index
     * @param string $alias
     */
    public function removeAlias($index, $alias)
    {
        $this->removeAliases([$index => $alias]);
    }
}
