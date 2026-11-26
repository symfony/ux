# Badge

Badges are used to inform the user of the status of specific data.

```twig {"preview":true}
<twig:Badge>Badge</twig:Badge>
```

## Installation

::: installation

## Usage

```twig
<twig:Badge
    color="default | neutral | primary | secondary | accent | info | success | warning | error"
    variant="default | outline | dash | soft | ghost"
    size="xs | sm | md | lg | xl"
>
    Badge
</twig:Badge>
```

## Examples

### Sizes

```twig {"preview":true}
<div class="flex flex-wrap items-center justify-center gap-2">
    <twig:Badge size="xs">Xsmall</twig:Badge>
    <twig:Badge size="sm">Small</twig:Badge>
    <twig:Badge>Medium</twig:Badge>
    <twig:Badge size="lg">Large</twig:Badge>
    <twig:Badge size="xl">Xlarge</twig:Badge>
</div>
```

### Colors

```twig {"preview":true}
<div class="flex flex-wrap items-center justify-center gap-2">
    <twig:Badge color="primary">Primary</twig:Badge>
    <twig:Badge color="secondary">Secondary</twig:Badge>
    <twig:Badge color="accent">Accent</twig:Badge>
    <twig:Badge color="neutral">Neutral</twig:Badge>
    <twig:Badge color="info">Info</twig:Badge>
    <twig:Badge color="success">Success</twig:Badge>
    <twig:Badge color="warning">Warning</twig:Badge>
    <twig:Badge color="error">Error</twig:Badge>
</div>
```

### Soft

```twig {"preview":true}
<div class="flex flex-wrap items-center justify-center gap-2">
    <twig:Badge variant="soft" color="primary">Primary</twig:Badge>
    <twig:Badge variant="soft" color="secondary">Secondary</twig:Badge>
    <twig:Badge variant="soft" color="accent">Accent</twig:Badge>
    <twig:Badge variant="soft" color="info">Info</twig:Badge>
    <twig:Badge variant="soft" color="success">Success</twig:Badge>
    <twig:Badge variant="soft" color="warning">Warning</twig:Badge>
    <twig:Badge variant="soft" color="error">Error</twig:Badge>
</div>
```

### Outline

```twig {"preview":true}
<div class="flex flex-wrap items-center justify-center gap-2">
    <twig:Badge variant="outline" color="primary">Primary</twig:Badge>
    <twig:Badge variant="outline" color="secondary">Secondary</twig:Badge>
    <twig:Badge variant="outline" color="accent">Accent</twig:Badge>
    <twig:Badge variant="outline" color="info">Info</twig:Badge>
    <twig:Badge variant="outline" color="success">Success</twig:Badge>
    <twig:Badge variant="outline" color="warning">Warning</twig:Badge>
    <twig:Badge variant="outline" color="error">Error</twig:Badge>
</div>
```

### Dash

```twig {"preview":true}
<div class="flex flex-wrap items-center justify-center gap-2">
    <twig:Badge variant="dash" color="primary">Primary</twig:Badge>
    <twig:Badge variant="dash" color="secondary">Secondary</twig:Badge>
    <twig:Badge variant="dash" color="accent">Accent</twig:Badge>
    <twig:Badge variant="dash" color="info">Info</twig:Badge>
    <twig:Badge variant="dash" color="success">Success</twig:Badge>
    <twig:Badge variant="dash" color="warning">Warning</twig:Badge>
    <twig:Badge variant="dash" color="error">Error</twig:Badge>
</div>
```

### Neutral With Outline Or Dash Style

These badges use dark text, only use them on light backgrounds.

```twig {"preview":true}
<div class="flex gap-2 rounded-box bg-white p-6">
    <twig:Badge color="neutral" variant="outline">Outline</twig:Badge>
    <twig:Badge color="neutral" variant="dash">Dash</twig:Badge>
</div>
```

### Ghost

```twig {"preview":true}
<twig:Badge variant="ghost">ghost</twig:Badge>
```

### Empty

```twig {"preview":true}
<div class="flex flex-wrap items-center justify-center gap-2">
    <twig:Badge color="primary" size="lg" />
    <twig:Badge color="primary" />
    <twig:Badge color="primary" size="sm" />
    <twig:Badge color="primary" size="xs" />
</div>
```

### With Icon

```twig {"preview":true}
<div class="flex flex-wrap items-center justify-center gap-2">
    <twig:Badge color="info">
        <svg class="size-[1em]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="currentColor" stroke-linejoin="miter" stroke-linecap="butt"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-linecap="square" stroke-miterlimit="10" stroke-width="2"></circle><path d="m12,17v-5.5c0-.276-.224-.5-.5-.5h-1.5" fill="none" stroke="currentColor" stroke-linecap="square" stroke-miterlimit="10" stroke-width="2"></path><circle cx="12" cy="7.25" r="1.25" fill="currentColor" stroke-width="2"></circle></g></svg>
        Info
    </twig:Badge>
    <twig:Badge color="success">
        <svg class="size-[1em]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="currentColor" stroke-linejoin="miter" stroke-linecap="butt"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-linecap="square" stroke-miterlimit="10" stroke-width="2"></circle><polyline points="7 13 10 16 17 8" fill="none" stroke="currentColor" stroke-linecap="square" stroke-miterlimit="10" stroke-width="2"></polyline></g></svg>
        Success
    </twig:Badge>
    <twig:Badge color="warning">
        <svg class="size-[1em]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18"><g fill="currentColor"><path d="M7.638,3.495L2.213,12.891c-.605,1.048,.151,2.359,1.362,2.359H14.425c1.211,0,1.967-1.31,1.362-2.359L10.362,3.495c-.605-1.048-2.119-1.048-2.724,0Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path><line x1="9" y1="6.5" x2="9" y2="10" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></line><path d="M9,13.569c-.552,0-1-.449-1-1s.448-1,1-1,1,.449,1,1-.448,1-1,1Z" fill="currentColor" data-stroke="none" stroke="none"></path></g></svg>
        Warning
    </twig:Badge>
    <twig:Badge color="error">
        <svg class="size-[1em]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="currentColor"><rect x="1.972" y="11" width="20.056" height="2" transform="translate(-4.971 12) rotate(-45)" fill="currentColor" stroke-width="0"></rect><path d="m12,23c-6.065,0-11-4.935-11-11S5.935,1,12,1s11,4.935,11,11-4.935,11-11,11Zm0-20C7.038,3,3,7.037,3,12s4.038,9,9,9,9-4.037,9-9S16.962,3,12,3Z" stroke-width="0" fill="currentColor"></path></g></svg>
        Error
    </twig:Badge>
</div>
```

### In A Text

```twig {"preview":true}
<div class="flex flex-col gap-2">
    <h1 class="text-xl font-semibold">
        Heading 1 <twig:Badge size="xl">Badge</twig:Badge>
    </h1>
    <h2 class="text-lg font-semibold">
        Heading 2 <twig:Badge size="lg">Badge</twig:Badge>
    </h2>
    <h3 class="text-base font-semibold">
        Heading 3 <twig:Badge>Badge</twig:Badge>
    </h3>
    <h4 class="text-sm font-semibold">
        Heading 4 <twig:Badge size="sm">Badge</twig:Badge>
    </h4>
    <h5 class="text-xs font-semibold">
        Heading 5 <twig:Badge size="xs">Badge</twig:Badge>
    </h5>
    <p class="text-xs">
        Paragraph <twig:Badge size="xs">Badge</twig:Badge>
    </p>
</div>
```

### In A Button

```twig {"preview":true}
<div class="flex flex-wrap items-center justify-center gap-2">
    <twig:Button>
        Inbox <twig:Badge size="sm">+99</twig:Badge>
    </twig:Button>
    <twig:Button>
        Inbox <twig:Badge size="sm" color="secondary">+99</twig:Badge>
    </twig:Button>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col gap-8">
    <div class="flex flex-wrap items-center gap-2" dir="rtl">
        <twig:Badge>شارة</twig:Badge>
        <twig:Badge color="primary">أساسي</twig:Badge>
        <twig:Badge color="secondary" variant="outline">ثانوي</twig:Badge>
        <twig:Badge color="info">
            <svg class="size-[1em]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="currentColor" stroke-linejoin="miter" stroke-linecap="butt"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-linecap="square" stroke-miterlimit="10" stroke-width="2"></circle><path d="m12,17v-5.5c0-.276-.224-.5-.5-.5h-1.5" fill="none" stroke="currentColor" stroke-linecap="square" stroke-miterlimit="10" stroke-width="2"></path><circle cx="12" cy="7.25" r="1.25" fill="currentColor" stroke-width="2"></circle></g></svg>
            معلومات
        </twig:Badge>
        <twig:Button>
            البريد الوارد <twig:Badge size="sm" color="secondary">+99</twig:Badge>
        </twig:Button>
    </div>
    <div class="flex flex-wrap items-center gap-2" dir="rtl">
        <twig:Badge>תג</twig:Badge>
        <twig:Badge color="primary">ראשי</twig:Badge>
        <twig:Badge color="secondary" variant="outline">משני</twig:Badge>
        <twig:Badge color="info">
            <svg class="size-[1em]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="currentColor" stroke-linejoin="miter" stroke-linecap="butt"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-linecap="square" stroke-miterlimit="10" stroke-width="2"></circle><path d="m12,17v-5.5c0-.276-.224-.5-.5-.5h-1.5" fill="none" stroke="currentColor" stroke-linecap="square" stroke-miterlimit="10" stroke-width="2"></path><circle cx="12" cy="7.25" r="1.25" fill="currentColor" stroke-width="2"></circle></g></svg>
            מידע
        </twig:Badge>
        <twig:Button>
            דואר נכנס <twig:Badge size="sm" color="secondary">+99</twig:Badge>
        </twig:Button>
    </div>
</div>
```

## API Reference

::: api-reference
