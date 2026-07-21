# Badge

The badge component can be used to complement other elements such as buttons or text elements as a label or to show the count of a given data, such as the number of comments for an article or how much time has passed by since a comment has been made.

```twig {"preview":true}
<div class="flex justify-center gap-4">
    <twig:Badge variant="brand">Brand</twig:Badge>
    <twig:Badge variant="alternative">Alternative</twig:Badge>
    <twig:Badge variant="gray">Gray</twig:Badge>
    <twig:Badge variant="danger">Danger</twig:Badge>
    <twig:Badge variant="success">Success</twig:Badge>
    <twig:Badge variant="warning">Warning</twig:Badge>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Badge
    variant="brand | alternative | gray | danger | success | warning"
    size="default | lg"
    shape="rounded | pill"
    border="none | bordered"
>
    Badge
</twig:Badge>
```

## Examples

### Bordered badges

This example can be used to add a border accent to the badge component.

```twig {"preview":true}
<div class="flex justify-center gap-4">
    <twig:Badge variant="brand" border="bordered">Brand</twig:Badge>
    <twig:Badge variant="alternative" border="bordered">Alternative</twig:Badge>
    <twig:Badge variant="gray" border="bordered">Gray</twig:Badge>
    <twig:Badge variant="danger" border="bordered">Danger</twig:Badge>
    <twig:Badge variant="success" border="bordered">Success</twig:Badge>
    <twig:Badge variant="warning" border="bordered">Warning</twig:Badge>
</div>
```

### Large badges

```twig {"preview":true}
<div class="flex justify-center gap-4">
    <twig:Badge variant="brand" size="lg">Brand</twig:Badge>
    <twig:Badge variant="alternative" size="lg">Alternative</twig:Badge>
    <twig:Badge variant="gray" size="lg">Gray</twig:Badge>
    <twig:Badge variant="danger" size="lg">Danger</twig:Badge>
    <twig:Badge variant="success" size="lg">Success</twig:Badge>
    <twig:Badge variant="warning" size="lg">Warning</twig:Badge>
</div>
```

### Pill badges

Use this example to make the corners even more rounded like pills for the badge component.

```twig {"preview":true}
<div class="flex justify-center gap-4">
    <twig:Badge variant="brand" shape="pill">Brand</twig:Badge>
    <twig:Badge variant="alternative" shape="pill">Alternative</twig:Badge>
    <twig:Badge variant="gray" shape="pill">Gray</twig:Badge>
    <twig:Badge variant="danger" shape="pill">Danger</twig:Badge>
    <twig:Badge variant="success" shape="pill">Success</twig:Badge>
    <twig:Badge variant="warning" shape="pill">Warning</twig:Badge>
</div>
```

### Badges as link

You can also use badges as anchor elements to link to another page.

```twig {"preview":true}
<div class="flex justify-center gap-4">
    <twig:Badge as="a" href="https://ux.symfony.com/" target="_blank" variant="brand" border="bordered">Brand</twig:Badge>
    <twig:Badge as="a" href="https://ux.symfony.com/" target="_blank" variant="alternative" border="bordered">Alternative</twig:Badge>
    <twig:Badge as="a" href="https://ux.symfony.com/" target="_blank" variant="gray" border="bordered">Gray</twig:Badge>
    <twig:Badge as="a" href="https://ux.symfony.com/" target="_blank" variant="danger" border="bordered">Danger</twig:Badge>
    <twig:Badge as="a" href="https://ux.symfony.com/" target="_blank" variant="success" border="bordered">Success</twig:Badge>
    <twig:Badge as="a" href="https://ux.symfony.com/" target="_blank" variant="warning" border="bordered">Warning</twig:Badge>
</div>
```

### Badges with icon

You can also use [SVG icons](https://ux.symfony.com/icons) inside the badge elements.

```twig {"preview":true}
<div class="flex justify-center gap-4">
    <twig:Badge variant="brand" border="bordered"><twig:ux:icon name="flowbite:clock-outline" class="h-3 w-3 me-1" aria-hidden="true"/> 2 min ago</twig:Badge>
    <twig:Badge variant="alternative" border="bordered"><twig:ux:icon name="flowbite:clock-outline" class="h-3 w-3 me-1" aria-hidden="true"/> 2 min ago</twig:Badge>
    <twig:Badge variant="gray" border="bordered"><twig:ux:icon name="flowbite:clock-outline" class="h-3 w-3 me-1" aria-hidden="true"/> 2 min ago</twig:Badge>
    <twig:Badge variant="danger" border="bordered"><twig:ux:icon name="flowbite:clock-outline" class="h-3 w-3 me-1" aria-hidden="true"/> 2 min ago</twig:Badge>
    <twig:Badge variant="success" border="bordered"><twig:ux:icon name="flowbite:clock-outline" class="h-3 w-3 me-1" aria-hidden="true"/> 2 min ago</twig:Badge>
    <twig:Badge variant="warning" border="bordered"><twig:ux:icon name="flowbite:clock-outline" class="h-3 w-3 me-1" aria-hidden="true"/> 2 min ago</twig:Badge>
</div>
```

## API Reference

::: api-reference
