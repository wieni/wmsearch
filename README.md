# WMSearch

Manages an index with a single document type `page`

The api allows adding different document types but only `page` is 'managed'.

# API

inject `wmsearch.api`

## Index

`$api->createIndex();`

`$api->deleteIndex();`

## Document

`$api->addDoc(DocumentInterface); // upsert`

`$api->delDoc($docType, $id);`

`$api->getDoc($docType, $id);`

## Search

`$api->search() : SearchResult`

`$api->highlightSearch() : SearchResult // Identical to ->search() but strips html from highlights`

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
