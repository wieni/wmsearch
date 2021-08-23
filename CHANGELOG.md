# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.10.6] - 2021-08-23
### Added
- Add changelog

### Changed
- Change queueing and indexing through admin form & Drush command to use Drupal batch

### Removed
- Remove support for the queue_ui module
- Remove `Indexer::purge` in favour of `IndexApi::recreate`
- Remove `Indexer::queueAll` and the rest of the class in favour of `IndexBatch`
