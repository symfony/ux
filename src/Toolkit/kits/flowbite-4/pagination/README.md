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

## API Reference

::: api-reference
