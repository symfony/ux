# Pagination

Indicate that related content is split across multiple pages with accessible pagination links.

```twig {"preview":true}
<twig:Pagination ariaLabel="Article pages">
    <twig:Pagination:Item label="Previous" disabled />
    <twig:Pagination:Item href="?page=1" label="1" active />
    <twig:Pagination:Item href="?page=2" label="2" />
    <twig:Pagination:Item href="?page=3" label="3" />
    <twig:Pagination:Item href="?page=2" label="Next" />
</twig:Pagination>
```

## Installation

::: installation

## Usage

```twig
<twig:Pagination ariaLabel="Search results pages">
    <twig:Pagination:Item href="?page=1" label="1" active />
    <twig:Pagination:Item href="?page=2" label="2" />
    <twig:Pagination:Item href="?page=3" label="3" />
</twig:Pagination>
```

## Accessibility

Give each pagination landmark a descriptive `ariaLabel`, especially when a page contains more than one pagination control.

The active item exposes `aria-current="page"`. Disabled items render as non-interactive `span` elements instead of links.

## Examples

### Overview

Use connected page links to navigate through a series of related pages.

```twig {"preview":true}
<twig:Pagination ariaLabel="Page navigation example">
    <twig:Pagination:Item href="#" label="Previous" />
    <twig:Pagination:Item href="#" label="1" />
    <twig:Pagination:Item href="#" label="2" />
    <twig:Pagination:Item href="#" label="3" />
    <twig:Pagination:Item href="#" label="Next" />
</twig:Pagination>
```

### Working with icons

When replacing previous and next labels with symbols, provide an accessible label and hide the symbol from assistive technologies.

```twig {"preview":true}
<twig:Pagination ariaLabel="Page navigation example">
    <twig:Pagination:Item href="#" ariaLabel="Previous"><span aria-hidden="true">&laquo;</span></twig:Pagination:Item>
    <twig:Pagination:Item href="#" label="1" />
    <twig:Pagination:Item href="#" label="2" />
    <twig:Pagination:Item href="#" label="3" />
    <twig:Pagination:Item href="#" ariaLabel="Next"><span aria-hidden="true">&raquo;</span></twig:Pagination:Item>
</twig:Pagination>
```

### Active

Mark the page currently being viewed as active. It can remain a link or render as a non-interactive item.

```twig {"preview":true}
<div>
    <twig:Pagination ariaLabel="Article pages">
        <twig:Pagination:Item href="#" label="Previous" />
        <twig:Pagination:Item href="#" label="1" />
        <twig:Pagination:Item href="#" label="2" active />
        <twig:Pagination:Item href="#" label="3" />
        <twig:Pagination:Item href="#" label="Next" />
    </twig:Pagination>

    <twig:Pagination ariaLabel="Article pages with a non-interactive current page" class="mt-3">
        <twig:Pagination:Item href="#" label="1" />
        <twig:Pagination:Item label="2" active />
        <twig:Pagination:Item href="#" label="3" />
    </twig:Pagination>
</div>
```

### Disabled

Disable unavailable navigation items without leaving an unusable link in the keyboard tab order.

```twig {"preview":true}
<twig:Pagination ariaLabel="Article pages">
    <twig:Pagination:Item label="Previous" disabled />
    <twig:Pagination:Item href="#" label="1" />
    <twig:Pagination:Item href="#" label="2" active />
    <twig:Pagination:Item href="#" label="3" />
    <twig:Pagination:Item href="#" label="Next" />
</twig:Pagination>
```

### Sizing

Use Bootstrap's large and small pagination sizes.

```twig {"preview":true}
<div class="d-flex flex-column gap-3">
    <twig:Pagination size="lg" ariaLabel="Large pagination example">
        <twig:Pagination:Item label="1" active />
        <twig:Pagination:Item href="#" label="2" />
        <twig:Pagination:Item href="#" label="3" />
    </twig:Pagination>

    <twig:Pagination size="sm" ariaLabel="Small pagination example">
        <twig:Pagination:Item label="1" active />
        <twig:Pagination:Item href="#" label="2" />
        <twig:Pagination:Item href="#" label="3" />
    </twig:Pagination>
</div>
```

### Alignment

Align the pagination links with Bootstrap flexbox utilities through the `align` prop.

```twig {"preview":true}
<div class="d-flex flex-column gap-3">
    <twig:Pagination align="center" ariaLabel="Centered page navigation example">
        <twig:Pagination:Item label="Previous" disabled />
        <twig:Pagination:Item href="#" label="1" />
        <twig:Pagination:Item href="#" label="2" />
        <twig:Pagination:Item href="#" label="3" />
        <twig:Pagination:Item href="#" label="Next" />
    </twig:Pagination>

    <twig:Pagination align="end" ariaLabel="End-aligned page navigation example">
        <twig:Pagination:Item label="Previous" disabled />
        <twig:Pagination:Item href="#" label="1" />
        <twig:Pagination:Item href="#" label="2" />
        <twig:Pagination:Item href="#" label="3" />
        <twig:Pagination:Item href="#" label="Next" />
    </twig:Pagination>
</div>
```

## Server Rendered

The examples above render static links. To paginate real data, pair the components with [Symfony UX Pagination](https://symfony.com/bundles/ux-pagination/current/index.html): it reads the current page from the request, fetches the matching slice of your query and generates the page URLs, while an application theme built with the `Pagination` components renders the navigation.

Install the bundle:

```bash
composer require symfony/ux-pagination
```

Paginate a query in your controller:

```php
// src/Controller/ProductController.php
use Symfony\UX\Pagination\PaginatorInterface;

#[Route('/products', name: 'product_index')]
public function index(ProductRepository $products, PaginatorInterface $paginator): Response
{
    $pagination = $paginator
        ->query($products->createQueryBuilder('product')->orderBy('product.name'))
        ->perPage(20)
        ->paginate();

    return $this->render('product/index.html.twig', [
        'products' => $pagination,
    ]);
}
```

Create a theme that renders the pagination result with the `Pagination` components:

```twig
{# templates/pagination/bootstrap.html.twig #}
{% if pagination.hasPrevious() or pagination.hasNext() %}
    <twig:Pagination ariaLabel="{{ 'Pagination'|trans({}, 'UXPaginationBundle') }}" {{ ...attributes }}>
        <twig:Pagination:Item
            href="{{ pagination.previousUrl }}"
            label="{{ 'Previous'|trans({}, 'UXPaginationBundle') }}"
            :disabled="not pagination.hasPrevious()"
        />
        {% for link in numbered ? pagination.pages : [] %}
            {% if link.isGap %}
                <twig:Pagination:Item label="…" disabled />
            {% else %}
                <twig:Pagination:Item
                    href="{{ link.url }}"
                    label="{{ link.page }}"
                    ariaLabel="{{ 'Go to page %page%'|trans({'%page%': link.page}, 'UXPaginationBundle') }}"
                    :active="link.isCurrent"
                />
            {% endif %}
        {% endfor %}
        <twig:Pagination:Item
            href="{{ pagination.nextUrl }}"
            label="{{ 'Next'|trans({}, 'UXPaginationBundle') }}"
            :disabled="not pagination.hasNext()"
        />
    </twig:Pagination>
{% endif %}
```

Select it as the default theme:

```yaml
# config/packages/ux_pagination.yaml
ux_pagination:
    theme: 'pagination/bootstrap.html.twig'
```

Then render the items and the navigation:

```twig
{# templates/product/index.html.twig #}
{% for product in products %}
    <article>{{ product.name }}</article>
{% endfor %}

{{ ux_pagination(products) }}
```

The theme receives the pagination result as `pagination`. Numbered results expose their pages through `pagination.pages`, where each entry is a page link (`page`, `url`, `isCurrent`) or a gap (`isGap`). Lookahead and cursor results set `numbered` to `false`, so the same theme renders only their previous and next links.

The `attributes` option of `ux_pagination()` is spread onto `Pagination`. `Pagination:Item` renders its own link, so `link_attributes` and the [LiveComponent integration](https://symfony.com/bundles/ux-pagination/current/live-component.html) that relies on them are not supported: use the built-in `@UXPagination/theme/bootstrap.html.twig` theme when you need them.

## API Reference

::: api-reference
