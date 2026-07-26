# Toggle Group

A set of two-state buttons that can be toggled on or off.

```twig {"preview":true}
<twig:ToggleGroup variant="outline" type="multiple">
    <twig:ToggleGroup:Item aria-label="Toggle bold">
        <twig:ux:icon name="lucide:bold" />
    </twig:ToggleGroup:Item>
    <twig:ToggleGroup:Item aria-label="Toggle italic">
        <twig:ux:icon name="lucide:italic" />
    </twig:ToggleGroup:Item>
    <twig:ToggleGroup:Item aria-label="Toggle strikethrough">
        <twig:ux:icon name="lucide:underline" />
    </twig:ToggleGroup:Item>
</twig:ToggleGroup>
```

## Installation

::: installation

## Usage

```twig
<twig:ToggleGroup type="single">
    <twig:ToggleGroup:Item value="a">A</twig:ToggleGroup:Item>
    <twig:ToggleGroup:Item value="b">B</twig:ToggleGroup:Item>
    <twig:ToggleGroup:Item value="c">C</twig:ToggleGroup:Item>
</twig:ToggleGroup>
```

## Examples

### Outline

```twig {"preview":true}
<twig:ToggleGroup variant="outline" type="single">
    <twig:ToggleGroup:Item aria-label="Toggle all" pressed>All</twig:ToggleGroup:Item>
    <twig:ToggleGroup:Item aria-label="Toggle missed">Missed</twig:ToggleGroup:Item>
</twig:ToggleGroup>
```

### Size

```twig {"preview":true}
<div class="flex flex-col gap-4">
    <twig:ToggleGroup variant="outline" size="sm" type="single">
        <twig:ToggleGroup:Item aria-label="Toggle top" pressed>Top</twig:ToggleGroup:Item>
        <twig:ToggleGroup:Item aria-label="Toggle bottom">Bottom</twig:ToggleGroup:Item>
        <twig:ToggleGroup:Item aria-label="Toggle left">Left</twig:ToggleGroup:Item>
        <twig:ToggleGroup:Item aria-label="Toggle right">Right</twig:ToggleGroup:Item>
    </twig:ToggleGroup>
    <twig:ToggleGroup variant="outline" type="single">
        <twig:ToggleGroup:Item aria-label="Toggle top" pressed>Top</twig:ToggleGroup:Item>
        <twig:ToggleGroup:Item aria-label="Toggle bottom">Bottom</twig:ToggleGroup:Item>
        <twig:ToggleGroup:Item aria-label="Toggle left">Left</twig:ToggleGroup:Item>
        <twig:ToggleGroup:Item aria-label="Toggle right">Right</twig:ToggleGroup:Item>
    </twig:ToggleGroup>
</div>
```

### Spacing

```twig {"preview":true}
<twig:ToggleGroup variant="outline" size="sm" type="single" spacing="2">
    <twig:ToggleGroup:Item aria-label="Toggle top" pressed>Top</twig:ToggleGroup:Item>
    <twig:ToggleGroup:Item aria-label="Toggle bottom">Bottom</twig:ToggleGroup:Item>
    <twig:ToggleGroup:Item aria-label="Toggle left">Left</twig:ToggleGroup:Item>
    <twig:ToggleGroup:Item aria-label="Toggle right">Right</twig:ToggleGroup:Item>
</twig:ToggleGroup>
```

### Vertical

```twig {"preview":true}
<twig:ToggleGroup type="multiple" orientation="vertical" spacing="1">
    <twig:ToggleGroup:Item aria-label="Toggle bold" pressed>
        <twig:ux:icon name="lucide:bold" />
    </twig:ToggleGroup:Item>
    <twig:ToggleGroup:Item aria-label="Toggle italic" pressed>
        <twig:ux:icon name="lucide:italic" />
    </twig:ToggleGroup:Item>
    <twig:ToggleGroup:Item aria-label="Toggle underline">
        <twig:ux:icon name="lucide:underline" />
    </twig:ToggleGroup:Item>
</twig:ToggleGroup>
```

### Disabled

```twig {"preview":true}
<twig:ToggleGroup disabled type="multiple">
    <twig:ToggleGroup:Item aria-label="Toggle bold">
        <twig:ux:icon name="lucide:bold" />
    </twig:ToggleGroup:Item>
    <twig:ToggleGroup:Item aria-label="Toggle italic">
        <twig:ux:icon name="lucide:italic" />
    </twig:ToggleGroup:Item>
    <twig:ToggleGroup:Item aria-label="Toggle underline">
        <twig:ux:icon name="lucide:underline" />
    </twig:ToggleGroup:Item>
</twig:ToggleGroup>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col items-center gap-4">
    {# Arabic #}
    <twig:ToggleGroup variant="outline" type="single" dir="rtl">
        <twig:ToggleGroup:Item aria-label="قائمة" pressed>قائمة</twig:ToggleGroup:Item>
        <twig:ToggleGroup:Item aria-label="شبكة">شبكة</twig:ToggleGroup:Item>
        <twig:ToggleGroup:Item aria-label="بطاقات">بطاقات</twig:ToggleGroup:Item>
    </twig:ToggleGroup>

    {# Hebrew #}
    <twig:ToggleGroup variant="outline" type="single" dir="rtl">
        <twig:ToggleGroup:Item aria-label="רשימה" pressed>רשימה</twig:ToggleGroup:Item>
        <twig:ToggleGroup:Item aria-label="רשת">רשת</twig:ToggleGroup:Item>
        <twig:ToggleGroup:Item aria-label="כרטיסים">כרטיסים</twig:ToggleGroup:Item>
    </twig:ToggleGroup>
</div>
```

## API Reference

::: api-reference
