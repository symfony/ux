# SignupForm

A signup page with form and image.

```twig {"preview":true}
<div class="flex min-h-svh flex-col items-center justify-center bg-muted p-6 md:p-10">
    <div class="w-full max-w-sm md:max-w-4xl">
        <twig:SignupForm />
    </div>
</div>
```

## Installation

::: installation

## Accessibility

- The block renders a real `<form>` with each `<label>` bound to its input through `for` and `id`, so clicking a label focuses its field and every field has an accessible name.
- Use `type="email"` and `type="password"` as shown, since they drive the on-screen keyboard and let password managers suggest a strong password.
- Add `autocomplete="name"`, `autocomplete="email"` and `autocomplete="new-password"` so the browser fills the right values.
- Point each input at its `Field:Description` with `aria-describedby`, so the hint is read with the field and not only seen.
- Render validation errors next to their field, point the field at the message with `aria-describedby` and set `aria-invalid="true"`. After a failed submit, move focus to the first invalid field.
- The provider buttons only show a logo. Each one carries its name in an `sr-only` span: keep it when you swap the logos, or the button has no accessible name.
- The decorative panel next to the form carries no text. Give any image inside it an empty `alt=""`, so it is not announced.
