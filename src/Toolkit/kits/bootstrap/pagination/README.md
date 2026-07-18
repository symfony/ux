# Pagination

Indicate that related content is split across multiple pages with accessible pagination links.

```twig {"preview":true,"height":"160px"}
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

```twig {"preview":true,"height":"160px"}
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

```twig {"preview":true,"height":"160px"}
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

```twig {"preview":true,"height":"210px"}
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
```

### Disabled

Disable unavailable navigation items without leaving an unusable link in the keyboard tab order.

```twig {"preview":true,"height":"160px"}
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

```twig {"preview":true,"height":"240px"}
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

```twig {"preview":true,"height":"230px"}
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

## API Reference

::: api-reference
