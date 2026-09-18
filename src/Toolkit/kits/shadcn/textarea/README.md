# Textarea

Displays a form textarea or a component that looks like a textarea.

```twig {"preview":true}
<div class="*:max-w-xs contents">
    <twig:Textarea placeholder="Type your message here." />
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Textarea />
```

## Examples

### Field

Use `Field`, `Field:Label`, and `Field:Description` to create a textarea with a label and description.

```twig {"preview":true}
<div class="*:max-w-xs contents">
    <twig:Field>
        <twig:Field:Label for="textarea-message">Message</twig:Field:Label>
        <twig:Field:Description>Enter your message below.</twig:Field:Description>
        <twig:Textarea id="textarea-message" placeholder="Type your message here." />
    </twig:Field>
</div>
```

### Disabled

Use the `disabled` attribute to disable the textarea. To style the disabled state, add the `data-disabled` attribute to the `Field` component.

```twig {"preview":true}
<div class="*:max-w-xs contents">
    <twig:Field data-disabled>
        <twig:Field:Label for="textarea-disabled">Message</twig:Field:Label>
        <twig:Textarea id="textarea-disabled" placeholder="Type your message here." disabled />
    </twig:Field>
</div>
```

### Invalid

Use the `aria-invalid` attribute to mark the textarea as invalid. To style the invalid state, add the `data-invalid` attribute to the `Field` component.

```twig {"preview":true}
<div class="*:max-w-xs contents">
    <twig:Field data-invalid>
        <twig:Field:Label for="textarea-invalid">Message</twig:Field:Label>
        <twig:Textarea id="textarea-invalid" placeholder="Type your message here." aria-invalid="true" />
        <twig:Field:Description>Please enter a valid message.</twig:Field:Description>
    </twig:Field>
</div>
```

### Button

Pair with `Button` to create a textarea with a submit button.

```twig {"preview":true}
<div class="*:max-w-xs contents">
    <div class="grid w-full gap-2">
        <twig:Textarea placeholder="Type your message here." />
        <twig:Button>Send message</twig:Button>
    </div>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="*:max-w-xs contents">
    <div class="flex flex-col gap-8 w-full">
        {# Arabic #}
        <twig:Field dir="rtl">
            <twig:Field:Label for="feedback-ar" dir="rtl">التعليقات</twig:Field:Label>
            <twig:Textarea id="feedback-ar" placeholder="تعليقاتك تساعدنا على التحسين..." dir="rtl" rows="4" />
            <twig:Field:Description dir="rtl">شاركنا أفكارك حول خدمتنا.</twig:Field:Description>
        </twig:Field>

        {# Hebrew #}
        <twig:Field dir="rtl">
            <twig:Field:Label for="feedback-he" dir="rtl">משוב</twig:Field:Label>
            <twig:Textarea id="feedback-he" placeholder="המשוב שלך עוזר לנו להתקדם..." dir="rtl" rows="4" />
            <twig:Field:Description dir="rtl">שתף את מחשבותיך על השירות שלנו.</twig:Field:Description>
        </twig:Field>
    </div>

</div>
```

## Accessibility

- `Textarea` renders a native `<textarea>`, so it keeps the browser's own keyboard handling and validation.
- Give it a visible `Label` bound with `for` and `id`. A placeholder is not a label: it disappears as soon as the user types and is not reliably announced.
- Set `aria-invalid="true"` for the invalid styling, and point `aria-describedby` at the message explaining what is wrong.
- When you show a character counter, put it in a live region with `aria-live="polite"` and reference it from the textarea with `aria-describedby`, so the limit is known before it is hit.
- The textarea grows with its content through `field-sizing`, so the text never scrolls out of sight at large font sizes.

## API Reference

::: api-reference
