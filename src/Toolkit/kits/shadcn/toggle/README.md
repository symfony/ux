# Toggle

A two-state button that can be either on or off.

```twig {"preview":true}
<twig:Toggle variant="outline" size="sm" aria-label="Toggle bookmark">
    <twig:ux:icon name="lucide:bookmark" class="group-data-[state=on]/toggle:fill-current" />
    Bookmark
</twig:Toggle>
```

## Installation

::: installation

## Usage

```twig
<twig:Toggle>
    Toggle
</twig:Toggle>
```

## Examples

### Outline

Use `variant="outline"` for an outline style.

```twig {"preview":true}
<div class="flex flex-wrap items-center gap-2">
    <twig:Toggle variant="outline" aria-label="Toggle italic">
        <twig:ux:icon name="lucide:italic" />
        Italic
    </twig:Toggle>
    <twig:Toggle variant="outline" aria-label="Toggle bold">
        <twig:ux:icon name="lucide:bold" />
        Bold
    </twig:Toggle>
</div>
```

### With Text

```twig {"preview":true}
<twig:Toggle aria-label="Toggle italic">
    <twig:ux:icon name="lucide:italic" />
    Italic
</twig:Toggle>
```

### Size

Use the `size` prop to change the size of the toggle.

```twig {"preview":true}
<div class="flex flex-wrap items-center gap-2">
    <twig:Toggle variant="outline" aria-label="Toggle small" size="sm">
        Small
    </twig:Toggle>
    <twig:Toggle variant="outline" aria-label="Toggle default" size="default">
        Default
    </twig:Toggle>
    <twig:Toggle variant="outline" aria-label="Toggle large" size="lg">
        Large
    </twig:Toggle>
</div>
```

### Disabled

```twig {"preview":true}
<div class="flex flex-wrap items-center gap-2">
    <twig:Toggle aria-label="Toggle disabled" disabled>
        Disabled
    </twig:Toggle>
    <twig:Toggle variant="outline" aria-label="Toggle disabled outline" disabled>
        Disabled
    </twig:Toggle>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col gap-8">
    {# Arabic #}
    <twig:Toggle variant="outline" size="sm" aria-label="Toggle bookmark" dir="rtl">
        <twig:ux:icon name="lucide:bookmark" class="group-aria-pressed/toggle:fill-foreground" />
        إشارة مرجعية
    </twig:Toggle>

    {# Hebrew #}
    <twig:Toggle variant="outline" size="sm" aria-label="Toggle bookmark" dir="rtl">
        <twig:ux:icon name="lucide:bookmark" class="group-aria-pressed/toggle:fill-foreground" />
        סימנייה
    </twig:Toggle>
</div>
```

## API Reference

::: api-reference
