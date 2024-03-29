# Upgrade Guide

This document describes breaking changes and how to upgrade. For a
complete list of changes including minor and patch releases, please
refer to the [`CHANGELOG`](CHANGELOG.md).

## 0.11.0
- If you extend BaseApi or any of its children: StatsApi, SearchApi, IndexApi, ReindexApi, TaskApi or AliasApi; you might need to update your constructor as the BaseApi has a new `$client` parameter.

## 0.10.6
- `Drupal\wmsearch\Service\Indexer` (`wmsearch.indexer`) is removed.
- `Drupal\wmsearch\Service\Batch\IndexBatch` (`wmsearch.batch.index`) is added as a replacement. The replacement is used in `Drupal\wmsearch\Form\OverviewForm` and in `Drupal\wmsearch\Commands\IndexCommands`
- The main difference is that the new service indexes using Drupal batch, so update your code accordingly. 
