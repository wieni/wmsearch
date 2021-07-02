# WMSearch

Manages an index with a single document type `page`

The api allows adding different document types but only `page` is 'managed'.

## Elasticsearch support
| Elasticsearch version | Last release | Development branch  |
|-----------------------|--------------|---------------------|
| 5.x                   | 0.9.6        | release/v0.9        |
| 6.x                   | /            | feature/elastic-6.x |
| 7.x                   | 0.10.5       | main                |

# API

inject `wmsearch.api.index`

## Index

`$api->createIndex();`

`$api->deleteIndex();`

## Document

`$api->addDoc(ElasticEntityInterface $entity); // upsert`

`$api->delDoc($id);`

`$api->getDoc($id);`

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

## ElasticEntityInterface

This assumes [wieni/wmmodel](https://github.com/wieni/wmmodel) or something similar.

```php
class Article extends Node implements WmModelInterface, ElasticEntityInterface
{
    use EntityPageTrait;

    public function getElasticTypes()
    {
        return ['page'];
    }

    public function getElasticDocumentCollection($type)
    {
        return 'mymodule.elastic.article.collection';
    }
}
```

## EntityDocumentCollectionInterface

```yaml
# mymodule.services.yml
services:
    mymodule.elastic.article.collection:
        class: Drupal\mymodule\Service\Elastic\Collection\ArticleCollection
```

```php
namespace Drupal\mymodule\Service\Elastic\Collection;

use Drupal\wmsearch\Entity\Document\EntityDocumentCollection;
use Drupal\wmsearch\Exception\NotIndexableException;

class ArticleCollection extends EntityDocumentCollection
{
    /** @var \Drupal\mymodule\Entity\Node\Article */
    protected $entity;

    public function toElasticArray($elasticId)
    {
        if (!$this->entity->isPublished()) {
            throw new NotIndexableException();
        }

        return [
            'id' => $this->entity->id(), // this isn't the elasticId
            'title' => $this->entity->getTitle(),
            'url' => $this->entity->toUrl()->toString(),
            'language' => $this->entity->language()->getId(),
            // ...
        ];
    }
}
```

## Programmatically index

```
$article = $nodeStorage->load(123);
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
