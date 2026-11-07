# Pagination

Pagination with page navigation, next and previous links.

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
            <twig:Pagination:Ellipsis />
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
            <twig:Pagination:Ellipsis />
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Next href="#" />
        </twig:Pagination:Item>
    </twig:Pagination:Content>
</twig:Pagination>
```

## Examples

### Simple

A simple pagination with only page numbers.

```twig {"preview":true}
<twig:Pagination>
    <twig:Pagination:Content>
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
            <twig:Pagination:Link href="#">4</twig:Pagination:Link>
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Link href="#">5</twig:Pagination:Link>
        </twig:Pagination:Item>
    </twig:Pagination:Content>
</twig:Pagination>
```

### Icons Only

Use just the previous and next buttons without page numbers. This is useful for data tables with a rows per page selector.

```twig {"preview":true}
<div class="flex items-center justify-between gap-4">
    <twig:Field orientation="horizontal" class="w-fit">
        <twig:Field:Label for="select-rows-per-page">Rows per page</twig:Field:Label>
        <twig:NativeSelect id="select-rows-per-page" class="w-20">
            <twig:NativeSelect:Option value="10">10</twig:NativeSelect:Option>
            <twig:NativeSelect:Option value="25" selected>25</twig:NativeSelect:Option>
            <twig:NativeSelect:Option value="50">50</twig:NativeSelect:Option>
            <twig:NativeSelect:Option value="100">100</twig:NativeSelect:Option>
        </twig:NativeSelect>
    </twig:Field>
    <twig:Pagination class="mx-0 w-auto">
        <twig:Pagination:Content>
            <twig:Pagination:Item>
                <twig:Pagination:Previous href="#" />
            </twig:Pagination:Item>
            <twig:Pagination:Item>
                <twig:Pagination:Next href="#" />
            </twig:Pagination:Item>
        </twig:Pagination:Content>
    </twig:Pagination>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col gap-8">
    {# Arabic #}
    <twig:Pagination dir="rtl">
        <twig:Pagination:Content>
            <twig:Pagination:Item>
                <twig:Pagination:Previous href="#" text="السابق" />
            </twig:Pagination:Item>
            <twig:Pagination:Item>
                <twig:Pagination:Link href="#">١</twig:Pagination:Link>
            </twig:Pagination:Item>
            <twig:Pagination:Item>
                <twig:Pagination:Link href="#" active>٢</twig:Pagination:Link>
            </twig:Pagination:Item>
            <twig:Pagination:Item>
                <twig:Pagination:Link href="#">٣</twig:Pagination:Link>
            </twig:Pagination:Item>
            <twig:Pagination:Item>
                <twig:Pagination:Ellipsis />
            </twig:Pagination:Item>
            <twig:Pagination:Item>
                <twig:Pagination:Next href="#" text="التالي" />
            </twig:Pagination:Item>
        </twig:Pagination:Content>
    </twig:Pagination>

    {# Hebrew #}
    <twig:Pagination dir="rtl">
        <twig:Pagination:Content>
            <twig:Pagination:Item>
                <twig:Pagination:Previous href="#" text="הקודם" />
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
                <twig:Pagination:Ellipsis />
            </twig:Pagination:Item>
            <twig:Pagination:Item>
                <twig:Pagination:Next href="#" text="הבא" />
            </twig:Pagination:Item>
        </twig:Pagination:Content>
    </twig:Pagination>
</div>
```

## UX Pagination Integration

The examples above only render static links. [Symfony UX Pagination](https://symfony.com/bundles/ux-pagination/current/index.html) paginates real data: it reads the current page from the request, fetches the matching slice of a query and generates the page URLs. Its `ux_pagination()` Twig function renders the navigation through a theme. Write that theme with this recipe's `Pagination` components, so the navigation matches your kit.

Create a theme that renders the pagination result with the `Pagination` components:

```twig
{# templates/pagination/shadcn.html.twig #}
{% if pagination.hasPrevious() or pagination.hasNext() %}
    <twig:Pagination aria-label="{{ 'Pagination'|trans({}, 'UXPaginationBundle') }}" {{ ...attributes }}>
        <twig:Pagination:Content {{ ...navigation_attributes }}>
            <twig:Pagination:Item>
                {% if pagination.hasPrevious() %}
                    <twig:Pagination:Previous
                        href="{{ pagination.previousUrl }}"
                        rel="prev"
                        text="{{ 'Previous'|trans({}, 'UXPaginationBundle') }}"
                        aria-label="{{ 'Previous page'|trans({}, 'UXPaginationBundle') }}"
                        {{ ...link_attributes.previous }}
                    />
                {% else %}
                    <twig:Pagination:Previous
                        aria-disabled="true"
                        class="pointer-events-none opacity-50"
                        text="{{ 'Previous'|trans({}, 'UXPaginationBundle') }}"
                        aria-label="{{ 'Previous page'|trans({}, 'UXPaginationBundle') }}"
                    />
                {% endif %}
            </twig:Pagination:Item>
            {% for link in numbered ? pagination.pages : [] %}
                <twig:Pagination:Item>
                    {% if link.isGap %}
                        <twig:Pagination:Ellipsis />
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
                    <twig:Pagination:Next
                        href="{{ pagination.nextUrl }}"
                        rel="next"
                        text="{{ 'Next'|trans({}, 'UXPaginationBundle') }}"
                        aria-label="{{ 'Next page'|trans({}, 'UXPaginationBundle') }}"
                        {{ ...link_attributes.next }}
                    />
                {% else %}
                    <twig:Pagination:Next
                        aria-disabled="true"
                        class="pointer-events-none opacity-50"
                        text="{{ 'Next'|trans({}, 'UXPaginationBundle') }}"
                        aria-label="{{ 'Next page'|trans({}, 'UXPaginationBundle') }}"
                    />
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
    theme: 'pagination/shadcn.html.twig'
```

Pass the result of a Symfony UX Pagination paginator (here `products`) to `ux_pagination()`:

```twig
{{ ux_pagination(products) }}
```

The theme also handles lookahead and cursor results, which only render the previous and next links. The `attributes`, `navigation_attributes` and `link_attributes` options of `ux_pagination()` reach the components, so the [LiveComponent integration](https://symfony.com/bundles/ux-pagination/current/live-component.html) works.

## Accessibility

- `Pagination` renders `role="navigation"` with `aria-label="pagination"`, so it is announced as a named landmark. Pass your own `aria-label` to replace it, for instance with a more specific label when the page holds several paginated lists.
- `Pagination:Link` marked `active` renders `aria-current="page"`, which is how a screen reader user knows which page they are on.
- `Pagination:Previous` and `Pagination:Next` carry `aria-label="Go to previous page"` and `aria-label="Go to next page"`. Translate them when your interface is not in English.
- `Pagination:Ellipsis` is hidden with `aria-hidden="true"` and carries a visually hidden "More pages" label, so the gap is explained without the glyph being read out.
- Render page links as real `<a>` elements with an `href`, so they work without JavaScript and can be opened in a new tab.

## API Reference

::: api-reference
