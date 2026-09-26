# Symfony UX Breadcrumb

**EXPERIMENTAL** This bundle is currently experimental and is likely to change,
possibly significantly, before its first stable release.

Declare the breadcrumb trail of a page on its controller, with a repeatable `#[Breadcrumb]` attribute.
The trail is collected unresolved onto the request, and only turned into labels and URLs when a template asks for it.
A redirect, a Turbo Stream or a JSON response pays nothing, even when the crumbs interpolate Doctrine associations.

## Installation

```bash
composer require symfony/ux-breadcrumb
```

## Usage

```php
// src/Controller/ProductViewController.php
namespace App\Controller;

use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;

#[Breadcrumb(label: 'product.index.breadcrumb', route: 'product_index')]
#[Breadcrumb(label: 'product.view.breadcrumb', translationParameters: ['name' => 'product.name'])]
final class ProductViewController extends AbstractController
{
    #[Route('/products/{slug}', name: 'product_view')]
    public function __invoke(Product $product): Response
    {
        return $this->render('product/view.html.twig');
    }
}
```

```twig
{# templates/product/view.html.twig #}
{{ ux_breadcrumb() }}
```

The crumbs are declared in trail order, top to bottom. The last one is the current
page: it is rendered as plain text carrying `aria-current="page"`, never as a link.

## The `#[Breadcrumb]` attribute

| Parameter               | Type                    | Purpose                                                                                      |
| ----------------------- | ----------------------- | -------------------------------------------------------------------------------------------- |
| `label`                 | `string`                | The translation key, or the literal label with `translationDomain: false`                    |
| `route`                 | `?string`               | Name of the route to link to                                                                 |
| `inheritedParameters`   | `array<int, string>`    | **A list of names** taken from the matched route                                             |
| `computedParameters`    | `array<string, string>` | **A map** of ExpressionLanguage expressions, evaluated against the controller's arguments    |
| `translationDomain`     | `string\|false\|null`   | `null` = default domain, a string = that domain, `false` = do not translate                  |
| `translationParameters` | `array<string, string>` | **A map** of ExpressionLanguage expressions, fed to the translator                           |
| `extra`                 | `array<string, mixed>`  | Arbitrary data forwarded untouched to the resolved item, such as an icon name or a CSS class |

Note the deliberate asymmetry between the three parameter bags:

- `inheritedParameters` is a **list of names** taken from the already-matched route (`_route_params`). The values exist, so nothing is evaluated.
- `computedParameters` is a **map** whose values are ExpressionLanguage expressions evaluated against the controller's arguments.

`translationParameters` is a map of expressions too, but it feeds the translator rather than the URL.

```php
#[Breadcrumb(
    label: 'product.view.breadcrumb',
    route: ProductRouteName::View->value,
    inheritedParameters: ['slug'],                           // reuse {slug} from the current route
    computedParameters: ['state' => 'product.state'],        // evaluate -> ?state=published
    translationParameters: ['name' => 'product.name'],       // evaluate -> ICU placeholder
)]
```

A route name is a string, as everywhere else in Symfony.
If your application keeps its route names in a backed enum, pass the case's `->value`, which is a valid constant expression in an attribute argument.

Neither bag decides whether a parameter lands in the path or in the query string.
The URL generator places each name in the path when the route declares a placeholder for it, and in the query string otherwise.
When both bags name the same parameter, the computed value wins.

Only the controller arguments a crumb expression actually names are kept on the trail, so the whole argument list, and notably the `Request`, is not pinned into the request attributes until render time.

## Twig

`ux_breadcrumb_items()` returns `list<BreadcrumbItem>`, each with `label`, `url` and `extra`.
Rendering the trail yourself is the expected path:

```twig
{% set items = ux_breadcrumb_items() %}

{% if items is not empty %}
    <nav aria-label="Breadcrumb">
        <ol>
            {% for item in items %}
                <li>
                    {# The last crumb is the current page, so it is never a link even when it
                       carries a route; a mid-trail crumb without a route degrades to a page
                       rather than rendering href="". #}
                    {% if not loop.last and item.url is not null %}
                        <a href="{{ item.url }}">{{ item.label }}</a>
                    {% else %}
                        <span aria-current="page">{{ item.label }}</span>
                    {% endif %}
                </li>
            {% endfor %}
        </ol>
    </nav>
{% endif %}
```

`ux_breadcrumb()` renders the bundled, deliberately unstyled theme for you.
Extend it and override its `*_class` blocks to attach your own classes:

```twig
{{ ux_breadcrumb() }}
{{ ux_breadcrumb({class: 'my-trail'}, theme: '@App/breadcrumb.html.twig') }}
```

```twig
{# templates/breadcrumb.html.twig #}
{% extends '@UXBreadcrumb/theme/default.html.twig' %}

{% block list_class %}flex items-center gap-2{% endblock %}
{% block current_class %}font-medium{% endblock %}
```

### Carrying your own data on a crumb

`extra` is never read by the bundle.
It is handed straight to the resolved item, so a template can do whatever it likes with it:

```php
#[Breadcrumb(label: 'product.index.breadcrumb', route: 'product_index', extra: ['icon' => 'tabler:package'])]
```

```twig
{% block item_label %}
    {% if item.extra.icon is defined %}<twig:ux:icon name="{{ item.extra.icon }}" />{% endif %}
    {{ item.label }}
{% endblock %}
```

### Absolute URLs for JSON-LD

`ux_breadcrumb_items(absolute: true)` gives **every** crumb a URL, including the current page, which is what schema.org's `BreadcrumbList` needs.
The two modes are resolved and memoized independently, so asking for both costs one resolution each:

```twig
{% set items = ux_breadcrumb_items(absolute: true) %}

{# A single crumb is not a hierarchy, so it is not worth marking up. #}
{% if items|length > 1 %}
    <script type="application/ld+json">
        {{- {
            '@context': 'https://schema.org',
            '@type': 'BreadcrumbList',
            itemListElement: items|map((item, index) => {
                '@type': 'ListItem',
                position: index + 1,
                name: item.label,
                item: item.url,
            }),
        }|json_encode|raw -}}
    </script>
{% endif %}
```

## Extension points

### Root crumbs

Application-wide crumbs, such as a "Home" or a section root, belong in a `RootCrumbProviderInterface`.
Providers run on every main request and whatever they yield is prepended to the trail.
Yielding nothing opts a route hierarchy out.

```php
namespace App\Breadcrumb;

use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;
use Symfony\UX\Breadcrumb\RootCrumbProviderInterface;

final class SectionRootCrumbProvider implements RootCrumbProviderInterface
{
    public function __invoke(string $route, Request $request): iterable
    {
        if (str_starts_with($route, 'app_admin_')) {
            yield new Breadcrumb(label: 'admin.home.breadcrumb', route: 'app_admin_home');
        }

        // The public site has no breadcrumb bar on its home page, so that page gets no trail.
        if (str_starts_with($route, 'app_website_') && 'app_website_home' !== $route) {
            yield new Breadcrumb(label: 'website.home.breadcrumb', route: 'app_website_home');
        }
    }
}
```

Implementations are autoconfigured.
Several can be registered, and they run in tag priority order.
Zero providers is a fully supported setup.
Yield more than one crumb to prepend a multi-level root.

### Expression functions

The bundle owns an `ExpressionLanguage` service backed by its own system cache pool:
crumb expressions are static strings parsed once, so a node-local, deploy-scoped pool
beats a shared cache round-trip. Add functions to it with a tagged provider:

```yaml
services:
    App\Breadcrumb\QueryExpressionLanguageProvider:
        tags: ['ux_breadcrumb.expression_function_provider']
```

The tag is deliberately not autoconfigured: `ExpressionFunctionProviderInterface` is
also implemented for the routing and security expression languages, and tagging every
one of them here would be wrong. Point `expression_language` at your own service id to
replace the whole thing.

### Crumbs only known at runtime

Inject `BreadcrumbTrailProvider` to append to the trail the listener already built:

```php
public function __invoke(Product $product, BreadcrumbTrailProvider $trailProvider): Response
{
    $trailProvider->getTrail()?->append(new Breadcrumb(
        label: $product->getName(),
        translationDomain: false,
    ));

    // ...
}
```

## Configuration

```yaml
# config/packages/ux_breadcrumb.yaml
ux_breadcrumb:
    # Request attribute the collected trail is stored on
    request_attribute: '_breadcrumbs'

    # Translation domain for crumbs that declare none; null uses the translator's default
    translation_domain: ~

    # Service id of the ExpressionLanguage used to evaluate crumb expressions
    expression_language: 'ux_breadcrumb.expression_language'

    # Twig template used by ux_breadcrumb()
    theme: '@UXBreadcrumb/theme/default.html.twig'
```

## Documentation

Read the [complete documentation](doc/index.rst) in this repository.

**This repository is a READ-ONLY subtree split.**
