# Textarea

Textarea allows users to enter text in multiple lines.

```twig {"preview":true}
<twig:Textarea placeholder="Bio" />
```

## Installation

::: installation

## Usage

```twig
<twig:Textarea
    color="default | neutral | primary | secondary | accent | info | success | warning | error"
    variant="default | ghost"
    size="xs | sm | md | lg | xl"
    name="bio"
    rows="4"
    placeholder="Bio"
>
    The initial text
</twig:Textarea>
```

## Examples

### Ghost

```twig {"preview":true}
<twig:Textarea variant="ghost" placeholder="Bio" />
```

### With Form Control And Labels

```twig {"preview":true}
<fieldset class="fieldset">
    <legend class="fieldset-legend">Your bio</legend>
    <twig:Textarea class="h-24" placeholder="Bio" />
    <div class="label">Optional</div>
</fieldset>
```

### Colors

```twig {"preview":true}
<div class="grid gap-4">
    <twig:Textarea color="primary" placeholder="Primary" />
    <twig:Textarea color="secondary" placeholder="Secondary" />
    <twig:Textarea color="accent" placeholder="Accent" />
    <twig:Textarea color="neutral" placeholder="Neutral" />
    <twig:Textarea color="info" placeholder="Info" />
    <twig:Textarea color="success" placeholder="Success" />
    <twig:Textarea color="warning" placeholder="Warning" />
    <twig:Textarea color="error" placeholder="Error" />
</div>
```

### Sizes

```twig {"preview":true}
<div class="grid gap-4">
    <twig:Textarea size="xs" placeholder="Bio" />
    <twig:Textarea size="sm" placeholder="Bio" />
    <twig:Textarea placeholder="Bio" />
    <twig:Textarea size="lg" placeholder="Bio" />
    <twig:Textarea size="xl" placeholder="Bio" />
</div>
```

### Disabled

```twig {"preview":true}
<twig:Textarea placeholder="Bio" disabled />
```

### With Initial Text

The initial text goes in the content of the component.

```twig {"preview":true}
<twig:Textarea name="bio" rows="4" placeholder="Bio">I build web applications with Symfony.</twig:Textarea>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col gap-8">
    <fieldset class="fieldset" dir="rtl">
        <legend class="fieldset-legend">نبذة عنك</legend>
        <twig:Textarea class="h-24" placeholder="نبذة" />
        <div class="label">اختياري</div>
    </fieldset>
    <fieldset class="fieldset" dir="rtl">
        <legend class="fieldset-legend">ספר על עצמך</legend>
        <twig:Textarea class="h-24" placeholder="ביוגרפיה" />
        <div class="label">אופציונלי</div>
    </fieldset>
</div>
```

## API Reference

::: api-reference
