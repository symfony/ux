# Breadcrumb

Displays the path to the current resource using a hierarchy of links.

```twig {"preview":true}
<twig:Breadcrumb>
    <twig:Breadcrumb:List>
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Link href="#">Home</twig:Breadcrumb:Link>
        </twig:Breadcrumb:Item>
        <twig:Breadcrumb:Separator />
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Ellipsis />
        </twig:Breadcrumb:Item>
        <twig:Breadcrumb:Separator />
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Link href="#">Components</twig:Breadcrumb:Link>
        </twig:Breadcrumb:Item>
        <twig:Breadcrumb:Separator />
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Page>Breadcrumb</twig:Breadcrumb:Page>
        </twig:Breadcrumb:Item>
    </twig:Breadcrumb:List>
</twig:Breadcrumb>
```

## Installation

::: installation

## Usage

```twig
<twig:Breadcrumb>
    <twig:Breadcrumb:List>
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Link href="#">Home</twig:Breadcrumb:Link>
        </twig:Breadcrumb:Item>
        <twig:Breadcrumb:Separator />
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Link href="#">Components</twig:Breadcrumb:Link>
        </twig:Breadcrumb:Item>
        <twig:Breadcrumb:Separator />
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Page>Breadcrumb</twig:Breadcrumb:Page>
        </twig:Breadcrumb:Item>
    </twig:Breadcrumb:List>
</twig:Breadcrumb>
```

## Examples

### Basic

A basic breadcrumb with a home link and a components link.

```twig {"preview":true}
<twig:Breadcrumb>
    <twig:Breadcrumb:List>
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Link href="#">Home</twig:Breadcrumb:Link>
        </twig:Breadcrumb:Item>
        <twig:Breadcrumb:Separator />
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Link href="#">Components</twig:Breadcrumb:Link>
        </twig:Breadcrumb:Item>
        <twig:Breadcrumb:Separator />
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Page>Breadcrumb</twig:Breadcrumb:Page>
        </twig:Breadcrumb:Item>
    </twig:Breadcrumb:List>
</twig:Breadcrumb>
```

### Custom separator

Use a custom component as `children` for `Breadcrumb:Separator` to create a custom separator.

```twig {"preview":true}
<twig:Breadcrumb>
    <twig:Breadcrumb:List>
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Link href="#">Home</twig:Breadcrumb:Link>
        </twig:Breadcrumb:Item>
        <twig:Breadcrumb:Separator>
            <twig:ux:icon name="lucide:dot" />
        </twig:Breadcrumb:Separator>
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Link href="#">Components</twig:Breadcrumb:Link>
        </twig:Breadcrumb:Item>
        <twig:Breadcrumb:Separator>
            <twig:ux:icon name="lucide:dot" />
        </twig:Breadcrumb:Separator>
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Page>Breadcrumb</twig:Breadcrumb:Page>
        </twig:Breadcrumb:Item>
    </twig:Breadcrumb:List>
</twig:Breadcrumb>
```

### Collapsed

We provide a `Breadcrumb:Ellipsis` component to show a collapsed state when the breadcrumb is too long.

```twig {"preview":true}
<twig:Breadcrumb>
    <twig:Breadcrumb:List>
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Link href="#">Home</twig:Breadcrumb:Link>
        </twig:Breadcrumb:Item>
        <twig:Breadcrumb:Separator />
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Ellipsis />
        </twig:Breadcrumb:Item>
        <twig:Breadcrumb:Separator />
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Link href="#">Components</twig:Breadcrumb:Link>
        </twig:Breadcrumb:Item>
        <twig:Breadcrumb:Separator />
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Page>Breadcrumb</twig:Breadcrumb:Page>
        </twig:Breadcrumb:Item>
    </twig:Breadcrumb:List>
</twig:Breadcrumb>
```

### Link component

To use a custom link component from your routing library, you can pass the `href` attribute to `Breadcrumb:Link`.

```twig {"preview":true}
<twig:Breadcrumb>
    <twig:Breadcrumb:List>
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Link href="#">Home</twig:Breadcrumb:Link>
        </twig:Breadcrumb:Item>
        <twig:Breadcrumb:Separator />
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Link href="#">Components</twig:Breadcrumb:Link>
        </twig:Breadcrumb:Item>
        <twig:Breadcrumb:Separator />
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Page>Breadcrumb</twig:Breadcrumb:Page>
        </twig:Breadcrumb:Item>
    </twig:Breadcrumb:List>
</twig:Breadcrumb>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col gap-6">
    <twig:Breadcrumb dir="rtl">
        <twig:Breadcrumb:List>
            <twig:Breadcrumb:Item>
                <twig:Breadcrumb:Link href="#">الرئيسية</twig:Breadcrumb:Link>
            </twig:Breadcrumb:Item>
            <twig:Breadcrumb:Separator>
                <twig:ux:icon name="lucide:dot" />
            </twig:Breadcrumb:Separator>
            <twig:Breadcrumb:Item>
                <twig:Breadcrumb:Link href="#">المكونات</twig:Breadcrumb:Link>
            </twig:Breadcrumb:Item>
            <twig:Breadcrumb:Separator>
                <twig:ux:icon name="lucide:dot" />
            </twig:Breadcrumb:Separator>
            <twig:Breadcrumb:Item>
                <twig:Breadcrumb:Page>مسار التنقل</twig:Breadcrumb:Page>
            </twig:Breadcrumb:Item>
        </twig:Breadcrumb:List>
    </twig:Breadcrumb>
    <twig:Breadcrumb dir="rtl">
        <twig:Breadcrumb:List>
            <twig:Breadcrumb:Item>
                <twig:Breadcrumb:Link href="#">בית</twig:Breadcrumb:Link>
            </twig:Breadcrumb:Item>
            <twig:Breadcrumb:Separator>
                <twig:ux:icon name="lucide:dot" />
            </twig:Breadcrumb:Separator>
            <twig:Breadcrumb:Item>
                <twig:Breadcrumb:Link href="#">רכיבים</twig:Breadcrumb:Link>
            </twig:Breadcrumb:Item>
            <twig:Breadcrumb:Separator>
                <twig:ux:icon name="lucide:dot" />
            </twig:Breadcrumb:Separator>
            <twig:Breadcrumb:Item>
                <twig:Breadcrumb:Page>ניווט</twig:Breadcrumb:Page>
            </twig:Breadcrumb:Item>
        </twig:Breadcrumb:List>
    </twig:Breadcrumb>
</div>
```

## Accessibility

- `Breadcrumb` renders a `<nav>` landmark with `aria-label="breadcrumb"`, and `Breadcrumb:List` a real `<ol>`, so the trail is announced as an ordered navigation.
- `Breadcrumb:Page` marks the current page with `aria-current="page"`, and carries `role="link"` with `aria-disabled="true"` so it reads as the link you are already on rather than as plain text.
- `Breadcrumb:Separator` is hidden with `aria-hidden="true"` and `role="presentation"`, so the chevron between items is never read out.
- `Breadcrumb:Ellipsis` is hidden the same way but carries a visually hidden "More" label, so the collapsed part of the trail is still explained.
- Keep the trail in document order from the root to the current page. The reading order is the markup order, whatever the styling does.

## API Reference

::: api-reference
