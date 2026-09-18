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

## Accessibility

- `Toggle` renders a real `<button>` with `aria-pressed` reflecting its state, so it is announced as a pressed or unpressed button and works with `Enter` and `Space`.
- An icon-only toggle has no text to announce. Give it an `aria-label`, or add a `<span class="sr-only">` label, since `<twig:ux:icon>` renders `aria-hidden="true"`.
- Label the toggle with what it does, not with its current state. `aria-pressed` already carries the state, so "Bold" is right and "Bold on" is not.
- Use `Toggle` for a formatting-style control that stays pressed. Use `Switch` for a setting that applies immediately, and `Checkbox` for a value submitted with a form.

## API Reference

::: api-reference
