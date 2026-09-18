# LoginForm

A two-column login page with a cover image.

```twig {"preview":true}
<div class="grid min-h-svh lg:grid-cols-2">
    <div class="flex flex-col gap-4 p-6 md:p-10">
        <div class="flex justify-center gap-2 md:justify-start">
            <a href="#" class="flex items-center gap-2 font-medium">
                <div class="flex size-6 items-center justify-center rounded-md bg-primary text-primary-foreground">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2h18"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M3 16h18"/></svg>
                </div>
                Acme Inc.
            </a>
        </div>
        <div class="flex flex-1 items-center justify-center">
            <div class="w-full max-w-xs">
                <twig:LoginForm />
            </div>
        </div>
    </div>
    <div class="relative hidden bg-muted lg:block">
        <img
            src="https://ui.shadcn.com/placeholder.svg"
            alt="Image"
            class="absolute inset-0 h-full w-full object-cover dark:brightness-[0.2] dark:grayscale"
        />
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
- The decorative panel next to the form carries no text. Give any image inside it an empty `alt=""`, so it is not announced.
