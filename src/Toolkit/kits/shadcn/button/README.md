# Button

Displays a button or a component that looks like a button.

```twig {"preview":true}
<div class="flex flex-wrap items-center gap-2 md:flex-row">
    <twig:Button variant="outline">Button</twig:Button>
    <twig:Button variant="outline" size="icon" aria-label="Submit">
        <twig:ux:icon name="lucide:arrow-up" />
    </twig:Button>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Button variant="outline">Button</twig:Button>
```

## Examples

### Size

Use the `size` prop to change the size of the button.

```twig {"preview":true}
<div class="flex flex-col items-start gap-8 sm:flex-row">
    <div class="flex items-start gap-2">
        <twig:Button size="xs" variant="outline">Extra Small</twig:Button>
        <twig:Button size="icon-xs" variant="outline" aria-label="Submit">
            <twig:ux:icon name="lucide:arrow-up-right" />
        </twig:Button>
    </div>
    <div class="flex items-start gap-2">
        <twig:Button size="sm" variant="outline">Small</twig:Button>
        <twig:Button size="icon-sm" variant="outline" aria-label="Submit">
            <twig:ux:icon name="lucide:arrow-up-right" />
        </twig:Button>
    </div>
    <div class="flex items-start gap-2">
        <twig:Button variant="outline">Default</twig:Button>
        <twig:Button size="icon" variant="outline" aria-label="Submit">
            <twig:ux:icon name="lucide:arrow-up-right" />
        </twig:Button>
    </div>
    <div class="flex items-start gap-2">
        <twig:Button size="lg" variant="outline">Large</twig:Button>
        <twig:Button size="icon-lg" variant="outline" aria-label="Submit">
            <twig:ux:icon name="lucide:arrow-up-right" />
        </twig:Button>
    </div>
</div>
```

### Default

```twig {"preview":true}
<twig:Button>Button</twig:Button>
```

### Outline

```twig {"preview":true}
<twig:Button variant="outline">Outline</twig:Button>
```

### Secondary

```twig {"preview":true}
<twig:Button variant="secondary">Secondary</twig:Button>
```

### Ghost

```twig {"preview":true}
<twig:Button variant="ghost">Ghost</twig:Button>
```

### Destructive

```twig {"preview":true}
<twig:Button variant="destructive">Destructive</twig:Button>
```

### Link

```twig {"preview":true}
<twig:Button variant="link">Link</twig:Button>
```

### Icon

```twig {"preview":true}
<twig:Button variant="outline" size="icon">
    <twig:ux:icon name="lucide:circle-fading-arrow-up" />
</twig:Button>
```

### With Icon

Remember to add the `data-icon="inline-start"` or `data-icon="inline-end"` attribute to the icon for the correct spacing.

```twig {"preview":true}
<twig:Button variant="outline" size="sm">
    <twig:ux:icon name="lucide:git-branch" /> New Branch
</twig:Button>
```

### Rounded

Use the `rounded-full` class to make the button rounded.

```twig {"preview":true}
<div class="flex flex-col gap-8">
    <twig:Button variant="outline" size="icon" class="rounded-full">
        <twig:ux:icon name="lucide:arrow-up" />
    </twig:Button>
</div>
```

### Spinner

Render a `Spinner` component inside the button to show a loading state. Remember to add the `data-icon="inline-start"` or `data-icon="inline-end"` attribute to the spinner for the correct spacing.

```twig {"preview":true}
<div class="flex gap-2">
    <twig:Button variant="outline" disabled>
        <twig:Spinner data-icon="inline-start" />
        Generating
    </twig:Button>
    <twig:Button variant="secondary" disabled>
        Downloading
        <twig:Spinner data-icon="inline-start" />
    </twig:Button>
</div>
```

### As Child

You can use the `as` prop on `Button` to make another element look like a button. Here's an example of a link that looks like a button.

```twig {"preview":true}
<twig:Button as="a" href="/login">Login</twig:Button>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col gap-8">
    <div class="flex flex-wrap items-center gap-2 md:flex-row" dir="rtl">
        <twig:Button variant="outline">زر</twig:Button>
        <twig:Button variant="destructive">حذف</twig:Button>
        <twig:Button variant="outline">
            إرسال
            <twig:ux:icon name="lucide:arrow-right" class="rtl:rotate-180" data-icon="inline-end" />
        </twig:Button>
        <twig:Button variant="outline" size="icon" aria-label="Add">
            <twig:ux:icon name="lucide:plus" />
        </twig:Button>
        <twig:Button variant="secondary" disabled>
            <twig:Spinner data-icon="inline-start" />
            جاري التحميل
        </twig:Button>
    </div>
    <div class="flex flex-wrap items-center gap-2 md:flex-row" dir="rtl">
        <twig:Button variant="outline">לחצן</twig:Button>
        <twig:Button variant="destructive">מחק</twig:Button>
        <twig:Button variant="outline">
            שלח
            <twig:ux:icon name="lucide:arrow-right" class="rtl:rotate-180" data-icon="inline-end" />
        </twig:Button>
        <twig:Button variant="outline" size="icon" aria-label="Add">
            <twig:ux:icon name="lucide:plus" />
        </twig:Button>
        <twig:Button variant="secondary" disabled>
            <twig:Spinner data-icon="inline-start" />
            טוען
        </twig:Button>
    </div>
</div>
```

## API Reference

::: api-reference
