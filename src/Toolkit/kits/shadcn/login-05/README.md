# LoginForm

A simple email-only login page.

```twig {"preview":true}
<div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
    <div class="w-full max-w-sm">
        <twig:LoginForm />
    </div>
</div>
```

## Installation

::: installation

## Accessibility

- The block renders a real `<form>` with each `<label>` bound to its input through `for` and `id`, so clicking a label focuses its field and every field has an accessible name.
- Use `type="email"` and `autocomplete="email"` as shown, since they drive the on-screen keyboard and let the browser fill the address.
- Render validation errors next to their field, point the field at the message with `aria-describedby` and set `aria-invalid="true"`. After a failed submit, move focus to the first invalid field.
- The logo link only shows an icon. Its `sr-only` text gives it a name, keep it when you change the logo.
- The provider buttons are `type="button"`, so they never submit the form by accident.
