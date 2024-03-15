# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.12.3] - 2024-03-15
### Fixed
- Fix Drupal deprecations

## [0.12.2] - 2024-02-13
### Added
- Make search API raw error message human-readable

## [0.12.1] - 2024-01-08
### Added
- Improve memory pressure when queueing large amounts of items

## [0.12.0] - 2023-09-12
### Added
- Add Drupal 10 compatibility

## [0.11.0] - 2022-06-13
### Added
- new `wmsearch.http.client` service and inject it into BaseApi

### Changed
- Allow Guzzle ^7.4.4 

## [0.10.9] - 2021-11-08
### Changed
- Catch 5xx and connection exceptions in addition to catching
  4xx exceptions when making calls to Elasticsearch.

## [0.10.8] - 2021-09-29
### Added
- Show index mapping & settings in overview form

### Fixed
- Fix error when trying to recreate non-existing index
- Fix `IndexApi::getMapping` after Elasticsearch update

## [0.10.7] - 2021-09-08
### Fixed
- Fix remaining count in queue batch

## [0.10.6] - 2021-08-23
See [`UPGRADING.md`](UPGRADING.md) for upgrade instructions.

### Added
- Add changelog
- Add a bit of documentation around Drush commands 

### Changed
- Change queueing and indexing through admin form & Drush command to use Drupal batch

### Removed
- Remove support for the queue_ui module
- Remove `Indexer::purge` in favour of `IndexApi::recreate`
- Remove `Indexer::queueAll` and the rest of the class in favour of `IndexBatch`
