# Label

Renders an accessible label associated with controls.

```twig {"preview":true}
<div class="flex gap-2">
    <twig:Checkbox id="terms" />
    <twig:Label for="terms">Accept terms and conditions</twig:Label>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Label for="email">Your email address</twig:Label>
```

## Examples

### Label in Field

For form fields, use the `Field` component which includes built-in label, description, and error handling.

```twig {"preview":true}
<div class="w-full max-w-md">
    <twig:Field>
        <twig:Field:Label for="email">Your email address</twig:Field:Label>
        <twig:Input id="email" />
    </twig:Field>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col gap-8">
    {# Arabic #}
    <div class="flex gap-2" dir="rtl">
        <twig:Checkbox id="terms-ar" />
        <twig:Label for="terms-ar">قبول الشروط والأحكام</twig:Label>
    </div>
    {# Hebrew #}
    <div class="flex gap-2" dir="rtl">
        <twig:Checkbox id="terms-he" />
        <twig:Label for="terms-he">קבל תנאים והגבלות</twig:Label>
    </div>
</div>
```

## API Reference

::: api-reference
