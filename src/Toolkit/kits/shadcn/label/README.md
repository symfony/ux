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

## Accessibility

- `Label` renders a native `<label>`. Bind it to its control with `for` pointing at the control's `id`, or wrap the control, so clicking the label focuses it and the control gets an accessible name.
- A label is the accessible name of the control. Do not replace it with a placeholder or with adjacent text that is not bound.
- Keep help text out of the label and in a separate element referenced with `aria-describedby`, so the name stays short.
- The disabled styling follows the control through `peer-disabled` and the `Field` disabled state, so a label does not need to repeat `disabled` itself.

## API Reference

::: api-reference
