# Breadcrumb

A navigation component that displays the current page's location within a website's hierarchy.

```twig {"preview":true}
<twig:Breadcrumb>
    <twig:Breadcrumb:List>
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Link href=".">Home</twig:Breadcrumb:Link>
        </twig:Breadcrumb:Item>
        <twig:Breadcrumb:Separator />
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Link href=".">Docs</twig:Breadcrumb:Link>
        </twig:Breadcrumb:Item>
        <twig:Breadcrumb:Separator />
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Link href=".">Components</twig:Breadcrumb:Link>
        </twig:Breadcrumb:Item>
        <twig:Breadcrumb:Separator />
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Page>Breadcrumb</twig:Breadcrumb:Page>
        </twig:Breadcrumb:Item>
    </twig:Breadcrumb:List>
</twig:Breadcrumb>
```

## Installation

<!-- Placeholder: Installation -->

## Usage

<!-- Placeholder: Usage -->

## Examples

### Default

```twig {"preview":true}
<twig:Breadcrumb>
    <twig:Breadcrumb:List>
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Link href=".">Home</twig:Breadcrumb:Link>
        </twig:Breadcrumb:Item>
        <twig:Breadcrumb:Separator />
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Link href=".">Docs</twig:Breadcrumb:Link>
        </twig:Breadcrumb:Item>
        <twig:Breadcrumb:Separator />
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Link href=".">Components</twig:Breadcrumb:Link>
        </twig:Breadcrumb:Item>
        <twig:Breadcrumb:Separator />
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Page>Breadcrumb</twig:Breadcrumb:Page>
        </twig:Breadcrumb:Item>
    </twig:Breadcrumb:List>
</twig:Breadcrumb>
```

### Custom Separator

```twig {"preview":true}
<twig:Breadcrumb>
    <twig:Breadcrumb:List>
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Link href=".">Home</twig:Breadcrumb:Link>
        </twig:Breadcrumb:Item>
        <twig:Breadcrumb:Separator>
            <twig:ux:icon name="lucide:slash" />
        </twig:Breadcrumb:Separator>
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Link href=".">Docs</twig:Breadcrumb:Link>
        </twig:Breadcrumb:Item>
        <twig:Breadcrumb:Separator>
            <twig:ux:icon name="lucide:slash" />
        </twig:Breadcrumb:Separator>
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Link href=".">Components</twig:Breadcrumb:Link>
        </twig:Breadcrumb:Item>
        <twig:Breadcrumb:Separator>
            <twig:ux:icon name="lucide:slash" />
        </twig:Breadcrumb:Separator>
        <twig:Breadcrumb:Item>
            <twig:Breadcrumb:Page>Breadcrumb</twig:Breadcrumb:Page>
        </twig:Breadcrumb:Item>
    </twig:Breadcrumb:List>
</twig:Breadcrumb>
```
