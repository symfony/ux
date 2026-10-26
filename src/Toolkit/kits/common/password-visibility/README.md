# Password Visibility

A Stimulus behavior that shows the password typed in a field as plain text, or hides it again, from a toggle button.

```twig {"preview":true,"collapseClass":true}
<div data-controller="password-visibility" class="flex w-72 flex-col gap-1.5">
    <label for="password" class="text-sm font-medium text-violet-900 dark:text-violet-100">Password</label>
    <div class="relative">
        <input type="password" id="password" value="my-super-secret-password" placeholder="Your password" autocomplete="current-password" spellcheck="false" autocapitalize="off" data-password-visibility-target="input" class="w-full rounded-md border border-violet-200 bg-violet-50 py-2 pl-3 pr-10 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">
        <button type="button" aria-controls="password" data-action="password-visibility#toggle" class="absolute inset-y-0 right-0 flex cursor-pointer items-center px-3 text-violet-500 hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-200">
            <svg data-password-visibility-target="show" role="img" aria-label="Show password" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            <svg data-password-visibility-target="hide" hidden role="img" aria-label="Hide password" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
        </button>
    </div>
</div>
```

## Installation

::: installation

## Usage

Add `data-controller="password-visibility"` around the password field and its toggle button. Mark the field as the `input` target, and trigger `password-visibility#toggle` from the button:

```twig
<div data-controller="password-visibility">
    <input type="password" id="password" data-password-visibility-target="input" spellcheck="false" autocapitalize="off">
    <button type="button" aria-controls="password" data-action="password-visibility#toggle">
        <span data-password-visibility-target="show">Show</span>
        <span data-password-visibility-target="hide" hidden>Hide</span>
    </button>
</div>
```

Each click switches the field between `password` and `text`, and shows either the `show` targets or the `hide` targets. An `<svg>` only hides with the `hidden` attribute when your CSS does it, as Tailwind CSS and Bootstrap do.

## Examples

### Text Labels

The `show` and `hide` targets can hold any content, like a text label.

```twig {"preview":true,"collapseClass":true}
<div data-controller="password-visibility" class="flex w-72 flex-col gap-1.5">
    <label for="new-password" class="text-sm font-medium text-violet-900 dark:text-violet-100">New password</label>
    <div class="flex gap-2">
        <input type="password" id="new-password" value="correct horse battery staple" autocomplete="new-password" spellcheck="false" autocapitalize="off" data-password-visibility-target="input" class="min-w-0 flex-1 rounded-md border border-violet-200 bg-violet-50 px-3 py-2 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">
        <button type="button" aria-controls="new-password" data-action="password-visibility#toggle" class="w-16 shrink-0 cursor-pointer rounded-md bg-violet-600 py-2 text-sm font-medium text-white hover:bg-violet-500">
            <span data-password-visibility-target="show">Show</span>
            <span data-password-visibility-target="hide" hidden>Hide</span>
        </button>
    </div>
</div>
```

## API Reference

::: api-reference
