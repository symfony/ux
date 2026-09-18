# LoginForm

A simple login form centered in a card.

```twig {"preview":true}
<div class="flex min-h-svh w-full items-center justify-center p-6 md:p-10">
    <div class="w-full max-w-sm">
        <twig:LoginForm />
    </div>
</div>
```

## Installation

::: installation

## Accessibility

- The block renders a real `<form>` with each `<label>` bound to its input through `for` and `id`, so clicking a label focuses its field and every field has an accessible name.
- Use `type="email"` and `type="password"` as shown, since they drive the on-screen keyboard and let password managers and browser autofill do their job.
- Add `autocomplete="email"` and `autocomplete="current-password"` so the browser can fill the form.
- Render validation errors next to their field, point the field at the message with `aria-describedby` and set `aria-invalid="true"`. After a failed submit, move focus to the first invalid field.
- The "Forgot your password?" link sits between the password label and its field. Keep it a real link so it stays in the tab order.
