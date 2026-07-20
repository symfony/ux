# Breadcrumb

Displays the path to the current resource using a hierarchy of links.

```twig {"preview":true,"height":"150px"}
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

```twig {"preview":true,"height":"150px"}
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

```twig {"preview":true,"height":"150px"}
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

```twig {"preview":true,"height":"150px"}
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

```twig {"preview":true,"height":"150px"}
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

## API Reference

::: api-reference
