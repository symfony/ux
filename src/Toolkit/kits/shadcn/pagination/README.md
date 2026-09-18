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
        <twig:Select id="select-rows-per-page" class="w-20">
            <option value="10">10</option>
            <option value="25" selected>25</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </twig:Select>
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

## Accessibility

- `Pagination` renders `role="navigation"` with `aria-label="pagination"`, so it is announced as a named landmark. Give it a more specific label when the page holds several paginated lists.
- `Pagination:Link` marked `active` renders `aria-current="page"`, which is how a screen reader user knows which page they are on.
- `Pagination:Previous` and `Pagination:Next` carry `aria-label="Go to previous page"` and `aria-label="Go to next page"`. Translate them when your interface is not in English.
- `Pagination:Ellipsis` is hidden with `aria-hidden="true"` and carries a visually hidden "More pages" label, so the gap is explained without the glyph being read out.
- Render page links as real `<a>` elements with an `href`, so they work without JavaScript and can be opened in a new tab.

## API Reference

::: api-reference
