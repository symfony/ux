# Symfony UX Pagination

**EXPERIMENTAL** This bundle is currently experimental and is likely to change,
possibly significantly, before its first stable release.

Ship stable cursor feeds or classic numbered pages through one request-aware
Symfony service. PHP owns the query, the built-in Twig themes render accessible
navigation, and the browser follows ordinary links without requiring
JavaScript.

## Installation

```bash
composer require symfony/ux-pagination
```

## Usage

```php
// src/Controller/EventController.php
namespace App\Controller;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Pagination\PaginatorInterface;

final class EventController extends AbstractController
{
    #[Route('/events', name: 'event_index')]
    public function __invoke(
        EventRepository $events,
        PaginatorInterface $paginator,
    ): Response {
        $pagination = $paginator
            ->cursor($events->createQueryBuilder('event'))
            ->orderBy(['createdAt', 'id'], 'DESC')
            ->perPage(20)
            ->paginate();

        return $this->render('event/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }
}
```

```twig
{# templates/event/index.html.twig #}
{% for event in pagination %}
    <article>{{ event.name }}</article>
{% endfor %}

{{ ux_pagination(pagination) }}
```

The paginator reads and validates `?cursor=`, preserves the current filters and
generates previous/next URLs. Use `query($source)` for numbered pagination or
`query($source)->lookahead()` when the UI needs only previous/next without an
exact total. Use `total($counter)` when an aggregate query needs an
application-provided total; invokable Symfony services work directly.

## What it provides

- First-class signed cursor pagination with forward and backward navigation
- Offset pagination with lazy totals and numbered pages
- Lookahead pagination without a count query
- Doctrine ORM 3 and DBAL 4.4+ adapters
- Callback builders and custom adapters for APIs, search engines and
  application sources
- Request-aware URLs generated through the Symfony Router
- Accessible, translated Twig navigation with Bootstrap, Tailwind and custom
  themes, plus validated attribute hooks
- Named paginator policies with Symfony autowiring
- PHP, Twig and JSON integration, plus LiveComponent support for numbered pages
- Real test helpers for page, URL and signed-cursor behavior

| Need                                                  | Strategy  |
| ----------------------------------------------------- | --------- |
| Resist offset shifts while ordered values stay stable | Cursor    |
| Page numbers and an exact total                       | Offset    |
| Previous/next without a total                         | Lookahead |

## Documentation

- [Getting started](doc/getting-started.rst)
- [Choosing a strategy](doc/strategies.rst)
- [Cursor pagination](doc/cursor.rst)
- [Rendering and customization](doc/rendering.rst)
- [Configuration](doc/configuration.rst)
- [Testing](doc/testing.rst) and [debugging](doc/debugging.rst)

Read the [complete documentation](doc/index.rst) in this repository.

**This repository is a READ-ONLY subtree split.**
