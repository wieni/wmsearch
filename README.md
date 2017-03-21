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

GET /search/json?q=lorem%20ipsum&o=0&a=10

```
q  string The query
o  int    The offset
a int    Amount of items
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


# Examples

## DocumentInterface

This assumes [wieni/wmmodel](https://github.com/wieni/wmmodel) or something similar.

```php
class Dish extends Node implements WmModelInterface, DocumentInterface
{
    use EntityPageTrait;

    public function toElasticArray($docType)
    {
        if ($docType !== 'page') {
            throw new \RuntimeException('Not prepared for that');
        }

        $bodies = array_filter(
            array_map(
                function (EckModel $item) {
                    if ($item instanceof Step) {
                        return $item->getDescription();
                    }

                    return false;
                },
                $this->getPreparation()
            )
        );

        $tags = array_map(
            function (HiddenTag $tag) {
                return $tag->getTitle();
            },
            $this->getTags()
        );

        $d = [
            'title' => $this->getTitle(),
            'intro' => $this->getDescription(),
            'created' => $this->getCreatedTime(),
            'changed' => $this->getChangedTime(),
            'body' => array_values($bodies),
            'terms' => $tags,
            'suggest' => $this->getTitle(),
        ];

        return $this->getBaseElasticArray($docType) + $d;
    }

    ...
}
```

```
$dish = $nodeStorage->load(123);
$api->addDoc($dish);
```

## Query

Search

```php
$perPage = 10;
$page = (int) $req->query->get('page');
$input = $req->query->get('q', '');

$query = new PageQuery();
$query->from($perPage * $page)
    ->size($perPage)
    ->setHighlight(1, 120, ['title', 'intro'], '<em>', '</em>')
    ->addMultiMatch($input, ['title', 'intro', 'body']);

$formatter->format($api->highlightSearch($query));
```

Completion

```php
$query = new PageQuery();
    ->setSource('')
    ->complete($input, 2);

$formatter->format($api->search($query));
```

# TODO

- DocumentInterface examples
- wmmodel implementation + reindex
