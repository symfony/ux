# Pagination

Use the Tailwind CSS pagination element to indicate a series of content across various pages based on multiple styles and sizes

```twig {"preview":true}
<twig:Pagination>
    <twig:Pagination:Content>
        <twig:Pagination:Item>
            <twig:Pagination:Previous href="#" />
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Link href="#">1</twig:Pagination:Link>
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Link href="#" active>2</twig:Pagination:Link>
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Link href="#">3</twig:Pagination:Link>
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Next href="#" />
        </twig:Pagination:Item>
    </twig:Pagination:Content>
</twig:Pagination>
```

## Installation

::: installation

## Usage

```twig
<twig:Pagination>
    <twig:Pagination:Content>
        <twig:Pagination:Item>
            <twig:Pagination:Previous href="#" />
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Link href="#">1</twig:Pagination:Link>
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Link href="#" active>2</twig:Pagination:Link>
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Link href="#">3</twig:Pagination:Link>
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Next href="#" />
        </twig:Pagination:Item>
    </twig:Pagination:Content>
</twig:Pagination>
```

## Examples

### As icon

The following pagination component example shows how you can use [SVG icons](https://ux.symfony.com/icons) instead of text to show the previous and next pages.

```twig {"preview":true}
<twig:Pagination>
    <twig:Pagination:Content>
        <twig:Pagination:Item>
            <twig:Pagination:Previous asIcon href="#" />
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Link href="#">1</twig:Pagination:Link>
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Link href="#" active>2</twig:Pagination:Link>
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Link href="#">3</twig:Pagination:Link>
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Next asIcon href="#" />
        </twig:Pagination:Item>
    </twig:Pagination:Content>
</twig:Pagination>
```

## UX Pagination Integration

The examples above only render static links. [Symfony UX Pagination](https://symfony.com/bundles/ux-pagination/current/index.html) paginates real data: it reads the current page from the request, fetches the matching slice of a query and generates the page URLs. Its `ux_pagination()` Twig function renders the navigation through a theme. Write that theme with this recipe's `Pagination` components, so the navigation matches your kit.

Create a theme that renders the pagination result with the `Pagination` components:

```twig
{# templates/pagination/flowbite-4.html.twig #}
{% if pagination.hasPrevious() or pagination.hasNext() %}
    <twig:Pagination aria-label="{{ 'Pagination'|trans({}, 'UXPaginationBundle') }}" {{ ...attributes }}>
        <twig:Pagination:Content {{ ...navigation_attributes }}>
            <twig:Pagination:Item>
                {% if pagination.hasPrevious() %}
                    <twig:Pagination:Previous href="{{ pagination.previousUrl }}" rel="prev" {{ ...link_attributes.previous }} />
                {% else %}
                    <twig:Button as="span" variant="secondary" aria-disabled="true" class="h-10 cursor-not-allowed opacity-50">Previous</twig:Button>
                {% endif %}
            </twig:Pagination:Item>
            {% for link in numbered ? pagination.pages : [] %}
                <twig:Pagination:Item>
                    {% if link.isGap %}
                        <twig:Button as="span" variant="secondary" size="icon" aria-hidden="true">&hellip;</twig:Button>
                    {% elseif link.isCurrent %}
                        <twig:Pagination:Link href="{{ link.url }}" active>{{ link.page }}</twig:Pagination:Link>
                    {% else %}
                        <twig:Pagination:Link
                            href="{{ link.url }}"
                            aria-label="{{ 'Go to page %page%'|trans({'%page%': link.page}, 'UXPaginationBundle') }}"
                            {{ ...link_attributes.pages[link.page]|default({}) }}
                        >{{ link.page }}</twig:Pagination:Link>
                    {% endif %}
                </twig:Pagination:Item>
            {% endfor %}
            <twig:Pagination:Item>
                {% if pagination.hasNext() %}
                    <twig:Pagination:Next href="{{ pagination.nextUrl }}" rel="next" {{ ...link_attributes.next }} />
                {% else %}
                    <twig:Button as="span" variant="secondary" aria-disabled="true" class="h-10 cursor-not-allowed opacity-50">Next</twig:Button>
                {% endif %}
            </twig:Pagination:Item>
        </twig:Pagination:Content>
    </twig:Pagination>
{% endif %}
```

Select it as the default theme:

```yaml
# config/packages/ux_pagination.yaml
ux_pagination:
    theme: 'pagination/flowbite-4.html.twig'
```

Pass the result of a Symfony UX Pagination paginator (here `products`) to `ux_pagination()`:

```twig
{{ ux_pagination(products) }}
```

The theme also handles lookahead and cursor results, which only render the previous and next links. The `attributes`, `navigation_attributes` and `link_attributes` options of `ux_pagination()` reach the components, so the [LiveComponent integration](https://symfony.com/bundles/ux-pagination/current/live-component.html) works.

## API Reference

::: api-reference
