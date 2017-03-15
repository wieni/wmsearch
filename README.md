# WMSearch

Manages an index with a single document type `page`

The api allows adding different document types but only `page` is 'managed'.

# API

inject `wmsearch.api`

## Index

Only implemented by Drupal\wmsearch\Service\Api, not by Drupal\wmsearch\Service\BaseApi.

`$api->createIndex();`

`$api->deleteIndex();`

## Document

`$api->addDoc(DocumentInterface); // upsert`

`$api->delDoc($docType, $id);`

`$api->getDoc($docType, $id);`

## Search

`$api->search(QueryInterface) : SearchResult`

```
// Identical to ->search() but strips html from highlights
$api->highlightSearch(QueryInterface) : SearchResult
```

## Misc

`$api->health(); // simple http elastic healthcheck`

`$api->refresh() // reopen lucene index`

`$api->flush() // fsync the lucene index`

# JSON

GET /search/json?q=lorem%20ipsum&p=1&pp=10

```
q  string The query
p  int    The page
pp int    Items per page
```
# Config

```yaml
# The elastic endpoint uri
wmsearch.elastic.endpoint: 'http://localhost:9200'

# Name of the index
wmsearch.elastic.index: 'mysite-staging'

# Serve a quick'n dirty site search on /simple-search
wmsearch.simple_search: false

# Default JSON endpoint
wmsearch.json.path: '/search/json'

# Formatter class for the JSON endpoint
wmsearch.json.formatter.class: 'Drupal\wmsearch\Service\ResultFormatter'

# Query provider for the JSON endpoint
wmsearch.json.query_builder.class: 'Drupal\wmsearch\Service\QueryBuilder'
```

# License

GPL

# TODO

- DocumentInterface examples
- wmmodel implementation + reindex
