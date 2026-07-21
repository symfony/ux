# Badge

Displays a badge or a component that looks like a badge.

```twig {"preview":true,"height":"150px"}
<div class="flex w-full flex-wrap justify-center gap-2">
    <twig:Badge>Badge</twig:Badge>
    <twig:Badge variant="secondary">Secondary</twig:Badge>
    <twig:Badge variant="destructive">Destructive</twig:Badge>
    <twig:Badge variant="outline">Outline</twig:Badge>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Badge variant="default | outline | secondary | destructive">
    Badge
</twig:Badge>
```

## Examples

### Variants

Use the `variant` prop to change the variant of the badge.

```twig {"preview":true,"height":"150px"}
<div class="flex flex-wrap gap-2">
    <twig:Badge>Default</twig:Badge>
    <twig:Badge variant="secondary">Secondary</twig:Badge>
    <twig:Badge variant="destructive">Destructive</twig:Badge>
    <twig:Badge variant="outline">Outline</twig:Badge>
    <twig:Badge variant="ghost">Ghost</twig:Badge>
</div>
```

### With Icon

You can render an icon inside the badge. Use `data-icon="inline-start"` to render the icon on the left and `data-icon="inline-end"` to render the icon on the right.

```twig {"preview":true,"height":"150px"}
<div class="flex flex-wrap gap-2">
    <twig:Badge variant="secondary">
        <twig:ux:icon name="lucide:badge-check" data-icon="inline-start" />
        Verified
    </twig:Badge>
    <twig:Badge variant="outline">
        Bookmark
        <twig:ux:icon name="lucide:bookmark" data-icon="inline-end" />
    </twig:Badge>
</div>
```

### With Spinner

You can render a spinner inside the badge. Remember to add the `data-icon="inline-start"` or `data-icon="inline-end"` attribute to the spinner.

```twig {"preview":true,"height":"150px"}
<div class="flex flex-wrap gap-2">
    <twig:Badge variant="destructive">
        <twig:Spinner data-icon="inline-start" />
        Deleting
    </twig:Badge>
    <twig:Badge variant="secondary">
        Generating
        <twig:Spinner data-icon="inline-end" />
    </twig:Badge>
</div>
```

### Link

Use the `as` prop to render a link as a badge.

```twig {"preview":true,"height":"150px"}
<twig:Badge as="a" href="#link">
    Open Link
    <twig:ux:icon name="lucide:arrow-up-right" data-icon="inline-end" />
</twig:Badge>
```

### Custom Colors

You can customize the colors of a badge by adding custom classes such as `bg-green-50 dark:bg-green-800` to the `Badge` component.

```twig {"preview":true,"height":"150px"}
<div class="flex flex-wrap gap-2">
    <twig:Badge class="bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300">Blue</twig:Badge>
    <twig:Badge class="bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300">Green</twig:Badge>
    <twig:Badge class="bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300">Sky</twig:Badge>
    <twig:Badge class="bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300">Purple</twig:Badge>
    <twig:Badge class="bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300">Red</twig:Badge>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col gap-6">
    <div class="flex w-full flex-wrap justify-center gap-2" dir="rtl">
        <twig:Badge>شارة</twig:Badge>
        <twig:Badge variant="secondary">ثانوي</twig:Badge>
        <twig:Badge variant="destructive">مدمر</twig:Badge>
        <twig:Badge variant="outline">مخطط</twig:Badge>
        <twig:Badge variant="secondary">
            <twig:ux:icon name="lucide:badge-check" data-icon="inline-start" />
            متحقق
        </twig:Badge>
        <twig:Badge variant="outline">
            إشارة مرجعية
            <twig:ux:icon name="lucide:bookmark" data-icon="inline-end" />
        </twig:Badge>
    </div>
    <div class="flex w-full flex-wrap justify-center gap-2" dir="rtl">
        <twig:Badge>תג</twig:Badge>
        <twig:Badge variant="secondary">משני</twig:Badge>
        <twig:Badge variant="destructive">הרסני</twig:Badge>
        <twig:Badge variant="outline">קווי מתאר</twig:Badge>
        <twig:Badge variant="secondary">
            <twig:ux:icon name="lucide:badge-check" data-icon="inline-start" />
            מאומת
        </twig:Badge>
        <twig:Badge variant="outline">
            סימנייה
            <twig:ux:icon name="lucide:bookmark" data-icon="inline-end" />
        </twig:Badge>
    </div>
</div>
```

## API Reference

::: api-reference
