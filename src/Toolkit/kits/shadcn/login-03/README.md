# LoginForm

A login page with a muted background color.

```twig {"preview":true}
<div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-muted p-6 md:p-10">
    <div class="flex w-full max-w-sm flex-col gap-6">
        <a href="#" class="flex items-center gap-2 self-center font-medium">
            <div class="flex size-6 items-center justify-center rounded-md bg-primary text-primary-foreground">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4"><path d="M7 2h10"/><path d="M5 6h14"/><rect width="18" height="12" x="3" y="10" rx="2"/></svg>
            </div>
            Acme Inc.
        </a>
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
- The provider buttons are `type="button"`, so they never submit the form by accident.
