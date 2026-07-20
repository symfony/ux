# Button

Use the button component inside forms, as links, social login, payment options with support for multiple styles, colors, sizes, gradients, and shadows

```twig {"preview":true}
<div class="space-x-2 space-y-2">
    <twig:Button>Default</twig:Button>
    <twig:Button variant="secondary">Secondary</twig:Button>
    <twig:Button variant="tertiary">Tertiary</twig:Button>
    <twig:Button variant="success">Success</twig:Button>
    <twig:Button variant="danger">Danger</twig:Button>
    <twig:Button variant="warning">Warning</twig:Button>
    <twig:Button variant="dark">Dark</twig:Button>
    <twig:Button variant="ghost">Ghost</twig:Button>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Button
    variant="brand | secondary | tertiary | success | danger | warning | dark | ghost | outline | outline-brand | outline-success | outline-danger | outline-warning"
    size="default | xs | sm | lg | xl | icon | icon-xs | icon-sm"
    shape="rounded | pill"
>
    Button
</twig:Button>
```

## Examples

### Button pills

The button pills can be used as an alternative style by using fully rounded edges.

```twig {"preview":true}
<div class="space-x-2 space-y-2">
    <twig:Button shape="pill">Default</twig:Button>
    <twig:Button shape="pill" variant="secondary">Secondary</twig:Button>
    <twig:Button shape="pill" variant="tertiary">Tertiary</twig:Button>
    <twig:Button shape="pill" variant="success">Success</twig:Button>
    <twig:Button shape="pill" variant="danger">Danger</twig:Button>
    <twig:Button shape="pill" variant="warning">Warning</twig:Button>
    <twig:Button shape="pill" variant="dark">Dark</twig:Button>
    <twig:Button shape="pill" variant="ghost">Ghost</twig:Button>
</div>
```

### Outline buttons

Use the following button styles to show the colors only for the border of the element.

```twig {"preview":true}
<div class="space-x-2 space-y-2">
    <twig:Button variant="outline">Outline</twig:Button>
    <twig:Button variant="outline-brand">Brand</twig:Button>
    <twig:Button variant="outline-success">Success</twig:Button>
    <twig:Button variant="outline-danger">Danger</twig:Button>
    <twig:Button variant="outline-warning">Warning</twig:Button>
</div>
```

### Button sizes

Use these examples if you want to use smaller or larger buttons.

```twig {"preview":true}
<div class="space-x-2 space-y-2">
    <twig:Button size="xs">Extra small</twig:Button>
    <twig:Button size="sm">Small</twig:Button>
    <twig:Button size="default">Base</twig:Button>
    <twig:Button size="lg">Large</twig:Button>
    <twig:Button size="xl">Extra large</twig:Button>
</div>
```

### Button with icon

Use the following examples to add a [SVG icons](https://ux.symfony.com/icons) inside the button either on the left or right side.

```twig {"preview":true}
<div class="flex items-center space-x-2">
    <twig:Button>
        <twig:ux:icon name="flowbite:cart-outline" class="w-4 h-4 me-1.5 -ms-0.5" aria-hidden="true"/>
        Buy now
    </twig:Button>

    <twig:Button>
        Choose plan
        <twig:ux:icon name="flowbite:arrow-right-outline"  class="w-4 h-4 ms-1.5 -me-0.5" aria-hidden="true"/>
    </twig:Button>
</div>
```

### Icon buttons

Sometimes you need a button to indicate an action using only an icon.

```twig {"preview":true}
<div class="space-x-2 space-y-2">
    <twig:Button size="icon-xs">
        <twig:ux:icon name="flowbite:heart-outline" class="w-5 h-5" aria-hidden="true"/>
        <span class="sr-only">Icon description</span>
    </twig:Button>

    <twig:Button size="icon-sm">
        <twig:ux:icon name="flowbite:heart-outline" class="w-5 h-5" aria-hidden="true"/>
        <span class="sr-only">Icon description</span>
    </twig:Button>

    <twig:Button size="icon">
        <twig:ux:icon name="flowbite:heart-outline" class="w-5 h-5" aria-hidden="true"/>
        <span class="sr-only">Icon description</span>
    </twig:Button>

    <twig:Button size="icon-xs" variant="outline">
        <twig:ux:icon name="flowbite:heart-outline" class="w-5 h-5" aria-hidden="true"/>
        <span class="sr-only">Icon description</span>
    </twig:Button>

    <twig:Button size="icon-sm" variant="outline">
        <twig:ux:icon name="flowbite:heart-outline" class="w-5 h-5" aria-hidden="true"/>
        <span class="sr-only">Icon description</span>
    </twig:Button>

    <twig:Button size="icon" variant="outline">
        <twig:ux:icon name="flowbite:heart-outline" class="w-5 h-5" aria-hidden="true"/>
        <span class="sr-only">Icon description</span>
    </twig:Button>
</div>
```

### Loader button

Use the following [spinner components](https://ux.symfony.com/toolkit/kits/flowbite-4/components/spinner) from Flowbite to indicate a loader animation inside buttons:

```twig {"preview":true}
<div class="space-x-2 space-y-2">
    <twig:Button>
        <twig:Spinner class="me-2" />
        Loading...
    </twig:Button>
</div>
```

## API Reference

::: api-reference
