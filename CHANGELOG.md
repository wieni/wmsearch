# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

### Added
- Show index mapping & settings in overview form

### Fixed
- Fix error when trying to recreate non-existing index

## [0.9.16] - 2021-08-23
See [`UPGRADING.md`](UPGRADING.md) for upgrade instructions.

### Added
- Add changelog
- Add Elasticsearch support table to README

### Changed
- Change queueing and indexing through admin form & Drush command to use Drupal batch

### Removed
- Remove support for the queue_ui module
- Remove `Indexer::purge` in favour of `IndexApi::recreate`
- Remove `Indexer::queueAll` and the rest of the class in favour of `IndexBatch`
- Remove Drupal as dependency since we use the SearchApi in Symfony projects as well. We should split the core querying 
  services into a different project.

