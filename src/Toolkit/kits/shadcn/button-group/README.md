# Button Group

A container that groups related buttons together with consistent styling.

```twig {"preview":true,"height":"200px"}
<twig:ButtonGroup>
    <twig:ButtonGroup class="hidden sm:flex">
        <twig:Button variant="outline" size="icon" aria-label="Go back">
            <twig:ux:icon name="lucide:arrow-left" />
        </twig:Button>
    </twig:ButtonGroup>
    <twig:ButtonGroup>
        <twig:Button variant="outline">Archive</twig:Button>
        <twig:Button variant="outline">Report</twig:Button>
    </twig:ButtonGroup>
    <twig:ButtonGroup>
        <twig:Button variant="outline">
            <twig:ux:icon name="lucide:clock" />
            Snooze
        </twig:Button>
        <twig:Button variant="outline" size="icon" aria-label="More options">
            <twig:ux:icon name="lucide:more-horizontal" />
        </twig:Button>
    </twig:ButtonGroup>
</twig:ButtonGroup>
```

## Installation

::: installation

## Usage

```twig
<twig:ButtonGroup>
    <twig:Button>Button 1</twig:Button>
    <twig:Button>Button 2</twig:Button>
</twig:ButtonGroup>
```

## Accessibility

- The `ButtonGroup` component has the `role` attribute set to `group`.
- Use `Tab` to navigate between the buttons in the group.
- Use `aria-label` or `aria-labelledby` to label the button group.

```twig {"preview":true,"height":"150px"}
<twig:ButtonGroup aria-label="Button group">
    <twig:Button>Button 1</twig:Button>
    <twig:Button>Button 2</twig:Button>
</twig:ButtonGroup>
```

## Examples

### Orientation

Set the `orientation` prop to change the button group layout.

```twig {"preview":true,"height":"200px"}
<twig:ButtonGroup orientation="vertical" aria-label="Media controls" class="h-fit">
    <twig:Button variant="outline" size="icon">
        <twig:ux:icon name="lucide:plus" />
    </twig:Button>
    <twig:Button variant="outline" size="icon">
        <twig:ux:icon name="lucide:minus" />
    </twig:Button>
</twig:ButtonGroup>
```

### Size

Control the size of buttons using the `size` prop on individual buttons.

```twig {"preview":true,"height":"300px"}
<div class="flex flex-col items-start gap-8">
    <twig:ButtonGroup>
        <twig:Button variant="outline" size="sm">Small</twig:Button>
        <twig:Button variant="outline" size="sm">Button</twig:Button>
        <twig:Button variant="outline" size="sm">Group</twig:Button>
        <twig:Button variant="outline" size="icon-sm">
            <twig:ux:icon name="lucide:plus" />
        </twig:Button>
    </twig:ButtonGroup>
    <twig:ButtonGroup>
        <twig:Button variant="outline">Default</twig:Button>
        <twig:Button variant="outline">Button</twig:Button>
        <twig:Button variant="outline">Group</twig:Button>
        <twig:Button variant="outline" size="icon">
            <twig:ux:icon name="lucide:plus" />
        </twig:Button>
    </twig:ButtonGroup>
    <twig:ButtonGroup>
        <twig:Button variant="outline" size="lg">Large</twig:Button>
        <twig:Button variant="outline" size="lg">Button</twig:Button>
        <twig:Button variant="outline" size="lg">Group</twig:Button>
        <twig:Button variant="outline" size="icon-lg">
            <twig:ux:icon name="lucide:plus" />
        </twig:Button>
    </twig:ButtonGroup>
</div>
```

### Nested

Nest `ButtonGroup` components to create button groups with spacing.

```twig {"preview":true,"height":"200px"}
<twig:ButtonGroup>
    <twig:ButtonGroup>
        <twig:Button variant="outline" size="icon">
            <twig:ux:icon name="lucide:plus" />
        </twig:Button>
    </twig:ButtonGroup>
    <twig:ButtonGroup>
        <twig:InputGroup>
            <twig:InputGroup:Input placeholder="Send a message..." />
            <twig:InputGroup:Addon align="inline-end">
                <twig:ux:icon name="lucide:audio-lines" />
            </twig:InputGroup:Addon>
        </twig:InputGroup>
    </twig:ButtonGroup>
</twig:ButtonGroup>
```

### Separator

The `ButtonGroup:Separator` component visually divides buttons within a group.

Buttons with variant `outline` do not need a separator since they have a border. For other variants, a separator is recommended to improve the visual hierarchy.

```twig {"preview":true,"height":"200px"}
<twig:ButtonGroup>
    <twig:Button variant="secondary" size="sm">Copy</twig:Button>
    <twig:ButtonGroup:Separator />
    <twig:Button variant="secondary" size="sm">Paste</twig:Button>
</twig:ButtonGroup>
```

### Split

Create a split button group by adding two buttons separated by a `ButtonGroup:Separator`.

```twig {"preview":true,"height":"200px"}
<twig:ButtonGroup>
    <twig:Button variant="secondary">Button</twig:Button>
    <twig:ButtonGroup:Separator />
    <twig:Button size="icon" variant="secondary">
        <twig:ux:icon name="tabler:plus" />
    </twig:Button>
</twig:ButtonGroup>
```

### Input

Wrap an `Input` component with buttons.

```twig {"preview":true,"height":"200px"}
<twig:ButtonGroup>
    <twig:Input placeholder="Search..." />
    <twig:Button variant="outline" aria-label="Search">
        <twig:ux:icon name="lucide:search" />
    </twig:Button>
</twig:ButtonGroup>
```

### Input Group

Wrap an `InputGroup` component to create complex input layouts.

```twig {"preview":true,"height":"200px"}
<twig:ButtonGroup class="[--radius:9999rem]">
    <twig:ButtonGroup>
        <twig:Button variant="outline" size="icon">
            <twig:ux:icon name="lucide:plus" />
        </twig:Button>
    </twig:ButtonGroup>
    <twig:ButtonGroup>
        <twig:InputGroup>
            <twig:InputGroup:Input placeholder="Send a message..." />
            <twig:InputGroup:Addon align="inline-end">
                <twig:ux:icon name="lucide:audio-lines" />
            </twig:InputGroup:Addon>
        </twig:InputGroup>
    </twig:ButtonGroup>
</twig:ButtonGroup>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true,"height":"320px"}
<div class="flex flex-col gap-6">
    <div dir="rtl">
        <twig:ButtonGroup>
            <twig:ButtonGroup class="hidden sm:flex">
                <twig:Button variant="outline" size="icon" aria-label="رجوع">
                    <twig:ux:icon name="lucide:arrow-right" />
                </twig:Button>
            </twig:ButtonGroup>
            <twig:ButtonGroup>
                <twig:Button variant="outline">أرشفة</twig:Button>
                <twig:Button variant="outline">تقرير</twig:Button>
            </twig:ButtonGroup>
            <twig:ButtonGroup>
                <twig:Button variant="outline">تأجيل</twig:Button>
                <twig:Button variant="outline" size="icon" aria-label="المزيد">
                    <twig:ux:icon name="lucide:more-horizontal" />
                </twig:Button>
            </twig:ButtonGroup>
        </twig:ButtonGroup>
    </div>
    <div dir="rtl">
        <twig:ButtonGroup>
            <twig:ButtonGroup class="hidden sm:flex">
                <twig:Button variant="outline" size="icon" aria-label="חזרה">
                    <twig:ux:icon name="lucide:arrow-right" />
                </twig:Button>
            </twig:ButtonGroup>
            <twig:ButtonGroup>
                <twig:Button variant="outline">ארכיון</twig:Button>
                <twig:Button variant="outline">דוח</twig:Button>
            </twig:ButtonGroup>
            <twig:ButtonGroup>
                <twig:Button variant="outline">דחה</twig:Button>
                <twig:Button variant="outline" size="icon" aria-label="עוד">
                    <twig:ux:icon name="lucide:more-horizontal" />
                </twig:Button>
            </twig:ButtonGroup>
        </twig:ButtonGroup>
    </div>
</div>
```

## API Reference

::: api-reference
