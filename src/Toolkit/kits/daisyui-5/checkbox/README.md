# Checkbox

Checkboxes are used to select or deselect a value.

```twig {"preview":true}
<twig:Checkbox checked aria-label="Checkbox" />
```

## Installation

::: installation

## Usage

```twig
<twig:Checkbox
    color="default | neutral | primary | secondary | accent | info | success | warning | error"
    size="xs | sm | md | lg | xl"
    name="remember"
    checked
/>
```

## Examples

### With Fieldset And Label

```twig {"preview":true}
<fieldset class="fieldset w-64 rounded-box border border-base-300 bg-base-100 p-4">
    <legend class="fieldset-legend">Login options</legend>
    <label class="label">
        <twig:Checkbox checked />
        Remember me
    </label>
</fieldset>
```

### Sizes

```twig {"preview":true}
<div class="flex flex-wrap items-center justify-center gap-2">
    <twig:Checkbox size="xs" checked aria-label="Xsmall" />
    <twig:Checkbox size="sm" checked aria-label="Small" />
    <twig:Checkbox checked aria-label="Medium" />
    <twig:Checkbox size="lg" checked aria-label="Large" />
    <twig:Checkbox size="xl" checked aria-label="Xlarge" />
</div>
```

### Colors

```twig {"preview":true}
<div class="flex flex-wrap items-center justify-center gap-2">
    <twig:Checkbox color="primary" checked aria-label="Primary" />
    <twig:Checkbox color="secondary" checked aria-label="Secondary" />
    <twig:Checkbox color="accent" checked aria-label="Accent" />
    <twig:Checkbox color="neutral" checked aria-label="Neutral" />
    <twig:Checkbox color="info" checked aria-label="Info" />
    <twig:Checkbox color="success" checked aria-label="Success" />
    <twig:Checkbox color="warning" checked aria-label="Warning" />
    <twig:Checkbox color="error" checked aria-label="Error" />
</div>
```

### Disabled

```twig {"preview":true}
<div class="flex flex-wrap items-center justify-center gap-2">
    <twig:Checkbox disabled aria-label="Disabled" />
    <twig:Checkbox disabled checked aria-label="Disabled and checked" />
</div>
```

### Indeterminate

A checkbox has no HTML attribute for the indeterminate state: set its `indeterminate` property from JavaScript.

```twig {"preview":true}
<twig:Checkbox id="indeterminate-checkbox" aria-label="Indeterminate" />

<script type="module">
    document.getElementById('indeterminate-checkbox').indeterminate = true;
</script>
```

### Custom Colors

```twig {"preview":true}
<twig:Checkbox checked aria-label="Custom colors" class="border-indigo-600 bg-indigo-500 checked:border-orange-500 checked:bg-orange-400 checked:text-orange-800" />
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col gap-8">
    <fieldset class="fieldset w-64 rounded-box border border-base-300 bg-base-100 p-4" dir="rtl">
        <legend class="fieldset-legend">خيارات تسجيل الدخول</legend>
        <label class="label">
            <twig:Checkbox checked />
            تذكرني
        </label>
        <label class="label">
            <twig:Checkbox color="primary" />
            أبقني متصلاً
        </label>
    </fieldset>
    <fieldset class="fieldset w-64 rounded-box border border-base-300 bg-base-100 p-4" dir="rtl">
        <legend class="fieldset-legend">אפשרויות התחברות</legend>
        <label class="label">
            <twig:Checkbox checked />
            זכור אותי
        </label>
        <label class="label">
            <twig:Checkbox color="primary" />
            השאר אותי מחובר
        </label>
    </fieldset>
</div>
```

## API Reference

::: api-reference
