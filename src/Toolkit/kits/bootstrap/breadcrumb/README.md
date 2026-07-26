# Breadcrumb

Indicates the current page's location within a navigational hierarchy.

```twig {"preview":true}
<twig:Breadcrumb>
    <li class="breadcrumb-item"><a href="#">Home</a></li>
    <li class="breadcrumb-item"><a href="#">Library</a></li>
    <li class="breadcrumb-item active" aria-current="page">Data</li>
</twig:Breadcrumb>
```

## Installation

::: installation

## Usage

```twig
<twig:Breadcrumb>
    <li class="breadcrumb-item"><a href="#">Home</a></li>
    <li class="breadcrumb-item active" aria-current="page">Library</li>
</twig:Breadcrumb>
```

## Accessibility

Give the navigation a meaningful accessible name. Apply `aria-current="page"` to the final breadcrumb item so assistive technologies can identify the current page.

## Examples

Build breadcrumb trails with linked items followed by an active item for the current page.

```twig {"preview":true}
<div class="d-flex flex-column gap-2">
    <twig:Breadcrumb>
        <li class="breadcrumb-item active" aria-current="page">Home</li>
    </twig:Breadcrumb>

    <twig:Breadcrumb>
        <li class="breadcrumb-item"><a href="#">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Library</li>
    </twig:Breadcrumb>

    <twig:Breadcrumb>
        <li class="breadcrumb-item"><a href="#">Home</a></li>
        <li class="breadcrumb-item"><a href="#">Library</a></li>
        <li class="breadcrumb-item active" aria-current="page">Data</li>
    </twig:Breadcrumb>
</div>
```

### Dividers

Customize the divider with text, an escaped SVG data URL, or an empty CSS custom property.

```twig {"preview":true}
<div class="d-flex flex-column gap-2">
    <twig:Breadcrumb divider=">">
        <li class="breadcrumb-item"><a href="#">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Library</li>
    </twig:Breadcrumb>

    <twig:Breadcrumb style="--bs-breadcrumb-divider: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='%236c757d'/%3E%3C/svg%3E&quot;);">
        <li class="breadcrumb-item"><a href="#">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Library</li>
    </twig:Breadcrumb>

    <twig:Breadcrumb style="--bs-breadcrumb-divider: ''">
        <li class="breadcrumb-item"><a href="#">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Library</li>
    </twig:Breadcrumb>
</div>
```

## API Reference

::: api-reference
