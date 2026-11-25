# Password Strength

A Stimulus behavior that rates a password as you type, the way Symfony's `PasswordStrength` constraint does, to drive a strength meter, a label and, optionally, the validity of the field.

```twig {"preview":true,"collapseClass":true}
<style>
    [data-password-strength-score-value="0"] .strength-segment:nth-child(-n+1) { background-color: #dc2626; }
    [data-password-strength-score-value="1"] .strength-segment:nth-child(-n+2) { background-color: #f97316; }
    [data-password-strength-score-value="2"] .strength-segment:nth-child(-n+3) { background-color: #eab308; }
    [data-password-strength-score-value="3"] .strength-segment:nth-child(-n+4) { background-color: #84cc16; }
    [data-password-strength-score-value="4"] .strength-segment { background-color: #16a34a; }
</style>
<div data-controller="password-strength" class="flex w-72 flex-col gap-1.5">
    <label for="password" class="text-sm font-medium text-violet-900 dark:text-violet-100">Password</label>
    <input type="password" id="password" autocomplete="new-password" aria-describedby="password-strength" data-password-strength-target="input" class="rounded-md border border-violet-200 bg-violet-50 px-3 py-2 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">
    <div aria-hidden="true" class="flex gap-1">
        <span class="strength-segment h-1.5 flex-1 rounded-full bg-violet-100 dark:bg-violet-900"></span>
        <span class="strength-segment h-1.5 flex-1 rounded-full bg-violet-100 dark:bg-violet-900"></span>
        <span class="strength-segment h-1.5 flex-1 rounded-full bg-violet-100 dark:bg-violet-900"></span>
        <span class="strength-segment h-1.5 flex-1 rounded-full bg-violet-100 dark:bg-violet-900"></span>
        <span class="strength-segment h-1.5 flex-1 rounded-full bg-violet-100 dark:bg-violet-900"></span>
    </div>
    <p id="password-strength" data-password-strength-target="label" aria-live="polite" class="min-h-5 text-sm text-violet-700 dark:text-violet-300"></p>
</div>
```

## Installation

::: installation

## Usage

Add `data-controller="password-strength"` around a password field marked as the `input` target, then show its strength with a `meter` target, a `label` target, or both:

```twig
<div data-controller="password-strength">
    <input type="password" data-password-strength-target="input">
    <meter data-password-strength-target="meter" min="0" max="4"></meter>
    <span data-password-strength-target="label"></span>
</div>
```

The password is rated as you type, from `0` (very weak) to `4` (very strong). The controller uses the algorithm of Symfony's [`PasswordStrength`](https://symfony.com/doc/current/reference/constraints/PasswordStrength.html) constraint, so the browser shows the same score as the server, unless your app [customizes the estimation](https://symfony.com/doc/current/reference/constraints/PasswordStrength.html#customizing-the-password-strength-estimation) of the constraint. The rating runs in the browser only, and never sends the password anywhere.

While the field holds a password, the controller element has a `data-password-strength-score-value` attribute with its score. Use it in CSS to style anything inside the element, as the preview above does to color one more segment per score:

```css
[data-password-strength-score-value='2'] .strength-segment:nth-child(-n + 3) {
    background-color: #eab308;
}
```

With Symfony Forms, set the `input` target through the `attr` option of the field:

```php
$builder->add('plainPassword', PasswordType::class, [
    'attr' => ['data-password-strength-target' => 'input'],
    'constraints' => [new PasswordStrength()],
]);
```

```twig
<div data-controller="password-strength">
    {{ form_row(form.plainPassword) }}
    <span data-password-strength-target="label"></span>
</div>
```

> [!WARNING]
> Anything checked in the browser can be bypassed: keep validating the password on the server, with the `PasswordStrength` constraint. The score also ignores data breaches: add the [`NotCompromisedPassword`](https://symfony.com/doc/current/reference/constraints/NotCompromisedPassword.html) constraint to reject a password that already leaked.

> [!NOTE]
> Every time the score changes, the controller dispatches a `password-strength:change` event, with the new score as `event.detail.score`, or `null` once the field is emptied. Listen for it to react from your own code.

## Examples

### Native Meter

Point the `meter` target at a `<meter>`: its value follows the score. Set its `low`, `high` and `optimum` attributes, and the browser colors it from red to green, without any CSS:

```twig {"preview":true,"collapseClass":true}
<div data-controller="password-strength" class="flex w-72 flex-col gap-1.5">
    <label for="password-meter" class="text-sm font-medium text-violet-900 dark:text-violet-100">Password</label>
    <input type="password" id="password-meter" autocomplete="new-password" data-password-strength-target="input" class="rounded-md border border-violet-200 bg-violet-50 px-3 py-2 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">
    <div class="flex items-center gap-3">
        <meter data-password-strength-target="meter" min="0" max="4" low="2" high="3" optimum="4" aria-label="Password strength" class="flex-1"></meter>
        <span data-password-strength-target="label" class="w-20 text-end text-sm text-violet-700 dark:text-violet-300"></span>
    </div>
</div>
```

### Custom Labels

Set `labels` to the five labels to show, from very weak to very strong, for instance to reword or translate them. Build it from Twig with the `json_encode` filter:

```twig {"preview":true,"collapseClass":true}
<div data-controller="password-strength" data-password-strength-labels-value="{{ ['Too weak', 'Weak', 'Fair', 'Good', 'Excellent']|json_encode }}" class="flex w-72 flex-col gap-1.5">
    <label for="passphrase" class="text-sm font-medium text-violet-900 dark:text-violet-100">Passphrase</label>
    <input type="password" id="passphrase" autocomplete="new-password" aria-describedby="passphrase-strength" data-password-strength-target="input" class="rounded-md border border-violet-200 bg-violet-50 px-3 py-2 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">
    <p id="passphrase-strength" data-password-strength-target="label" aria-live="polite" class="min-h-5 text-sm text-violet-700 dark:text-violet-300"></p>
</div>
```

### Block Weak Passwords

Set `minScore` to refuse weaker passwords, like the [`minScore`](https://symfony.com/doc/current/reference/constraints/PasswordStrength.html#minscore) option of the `PasswordStrength` constraint. Below it, the field is invalid, so the browser blocks the submission and shows the `message`. Here, only a strong password goes through. The field of this preview has no `name`, so submitting it never sends what you type.

> [!TIP]
> Use the same `minScore` as your constraint, and show its message the way Symfony translates it: `data-password-strength-message-value="{{ 'The password strength is too low. Please use a stronger password.'|trans({}, 'validators') }}"`.

```twig {"preview":true,"collapseClass":true}
<style>
    #new-password:user-invalid { border-color: #dc2626; }
</style>
<form action="/link/account/password" method="post" data-controller="password-strength" data-password-strength-min-score-value="3" class="flex w-72 flex-col gap-1.5">
    <label for="new-password" class="text-sm font-medium text-violet-900 dark:text-violet-100">New password</label>
    <input type="password" id="new-password" required autocomplete="new-password" aria-describedby="new-password-strength" data-password-strength-target="input" class="rounded-md border border-violet-200 bg-violet-50 px-3 py-2 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">
    <p id="new-password-strength" data-password-strength-target="label" aria-live="polite" class="min-h-5 text-sm text-violet-700 dark:text-violet-300"></p>
    <button type="submit" class="cursor-pointer rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-500">Change password</button>
</form>
```

## API Reference

::: api-reference
