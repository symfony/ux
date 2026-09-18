# Separator

Visually or semantically separates content.

```twig {"preview":true}
<div class="flex max-w-sm flex-col gap-4 text-sm">
    <div class="flex flex-col gap-1.5">
        <div class="leading-none font-medium">shadcn/ui</div>
        <div class="text-muted-foreground">The Foundation for your Design System</div>
    </div>
    <twig:Separator />
    <div>A set of beautifully designed components that you can customize, extend, and build on.</div>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Separator />
```

## Examples

### Vertical

Use `orientation="vertical"` for a vertical separator.

```twig {"preview":true}
<div class="flex h-5 items-center gap-4 text-sm">
    <div>Blog</div>
    <twig:Separator orientation="vertical" />
    <div>Docs</div>
    <twig:Separator orientation="vertical" />
    <div>Source</div>
</div>
```

### Menu

Vertical separators between menu items with descriptions.

```twig {"preview":true}
<div class="flex items-center gap-2 text-sm md:gap-4">
    <div class="flex flex-col gap-1">
        <span class="font-medium">Settings</span>
        <span class="text-xs text-muted-foreground">Manage preferences</span>
    </div>
    <twig:Separator orientation="vertical" />
    <div class="flex flex-col gap-1">
        <span class="font-medium">Account</span>
        <span class="text-xs text-muted-foreground">Profile &amp; security</span>
    </div>
    <twig:Separator orientation="vertical" class="hidden md:block" />
    <div class="hidden flex-col gap-1 md:flex">
        <span class="font-medium">Help</span>
        <span class="text-xs text-muted-foreground">Support &amp; docs</span>
    </div>
</div>
```

### List

Horizontal separators between list items.

```twig {"preview":true}
<div class="flex w-full max-w-sm flex-col gap-2 text-sm">
    <dl class="flex items-center justify-between">
        <dt>Item 1</dt>
        <dd class="text-muted-foreground">Value 1</dd>
    </dl>
    <twig:Separator />
    <dl class="flex items-center justify-between">
        <dt>Item 2</dt>
        <dd class="text-muted-foreground">Value 2</dd>
    </dl>
    <twig:Separator />
    <dl class="flex items-center justify-between">
        <dt>Item 3</dt>
        <dd class="text-muted-foreground">Value 3</dd>
    </dl>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col items-center gap-8">
    {# Arabic #}
    <div class="flex max-w-sm flex-col gap-4 text-sm" dir="rtl">
        <div class="flex flex-col gap-1.5">
            <div class="leading-none font-medium">shadcn/ui</div>
            <div class="text-muted-foreground">الأساس لنظام التصميم الخاص بك</div>
        </div>
        <twig:Separator />
        <div>مجموعة من المكونات المصممة بشكل جميل يمكنك تخصيصها وتوسيعها والبناء عليها.</div>
    </div>

    {# Hebrew #}
    <div class="flex max-w-sm flex-col gap-4 text-sm" dir="rtl">
        <div class="flex flex-col gap-1.5">
            <div class="leading-none font-medium">shadcn/ui</div>
            <div class="text-muted-foreground">הבסיס למערכת העיצוב שלך</div>
        </div>
        <twig:Separator />
        <div>קבוצה של רכיבים מעוצבים בצורה טובה שניתן להתאים אישית, להרחיב ולבנות עליהם.</div>
    </div>
</div>
```

## Accessibility

- `Separator` renders `role="none"` by default, so a purely visual divider is not announced. Pass `decorative="false"` when the divider marks a real boundary, which renders `role="separator"` with an `aria-orientation`.
- A separator takes its name from `aria-label`, not from its contents, so do not put text inside a semantic one. Use `Marker` with the `separator` variant for a labelled divider.
- `aria-orientation` is only emitted on a semantic separator, since it means nothing on a decorative one.
- Prefer a heading or a landmark over a separator when the boundary is structural. A separator marks a break, it does not name the sections around it.

## API Reference

::: api-reference
