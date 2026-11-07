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

## UX Pagination Integration

The examples above only render static links. [Symfony UX Pagination](https://symfony.com/bundles/ux-pagination/current/index.html) paginates real data: it reads the current page from the request, fetches the matching slice of a query and generates the page URLs. Its `ux_pagination()` Twig function renders the navigation through a theme. Write that theme with this recipe's `Pagination` components, so the navigation matches your kit.

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

Pass the result of a Symfony UX Pagination paginator (here `products`) to `ux_pagination()`:

```twig
{{ ux_pagination(products) }}
```

The theme also handles lookahead and cursor results, which only render the previous and next links. The Bootstrap components ignore the `navigation_attributes` and `link_attributes` options, so the [LiveComponent integration](https://symfony.com/bundles/ux-pagination/current/live-component.html) does not work with this theme: use the built-in `@UXPagination/theme/bootstrap.html.twig` theme instead.

## API Reference

::: api-reference
