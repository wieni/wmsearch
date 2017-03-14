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


# Config

```yaml
# The elastic endpoint uri
wmsearch.elastic.endpoint: 'http://localhost:9200'

# Name of the index
wmsearch.elastic.index: 'mysite-staging'

# Serve a quick'n dirty site search on /simple-search
wmsearch.simple_search: false
```

# License

GPL

# TODO

- DocumentInterface examples
- wmmodel implementation + reindex
- BaseApi + some json formatter endpoint / example
- BaseApi: light bootstrap: how to retrieve wmsearch.elastic.endpoint and wmsearch.elastic.index? (kernel event?)
