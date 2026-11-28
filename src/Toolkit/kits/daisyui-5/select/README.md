# Select

Select is used to pick a value from a list of options.

```twig {"preview":true}
<twig:Select>
    <option disabled selected>Pick a color</option>
    <option>Crimson</option>
    <option>Amber</option>
    <option>Velvet</option>
</twig:Select>
```

## Installation

::: installation

## Usage

```twig
<twig:Select
    color="default | neutral | primary | secondary | accent | info | success | warning | error"
    variant="default | ghost"
    size="xs | sm | md | lg | xl"
    as="select | label"
    name="color"
>
    <option>Crimson</option>
    <option>Amber</option>
</twig:Select>
```

To add an icon or a text next to the select, render the component as a `label` that wraps a plain `<select>`. The props then style the `label`, and the attributes of the field go on the inner `<select>`:

```twig
<twig:Select as="label" color="primary">
    <span class="label">Type</span>
    <select name="type">
        <option>Personal</option>
        <option>Business</option>
    </select>
</twig:Select>
```

## Examples

### Ghost

The ghost style has no background.

```twig {"preview":true}
<twig:Select variant="ghost">
    <option disabled selected>Pick a font</option>
    <option>Inter</option>
    <option>Poppins</option>
    <option>Raleway</option>
</twig:Select>
```

### With Fieldset And Labels

```twig {"preview":true}
<fieldset class="fieldset w-xs">
    <legend class="fieldset-legend">Browsers</legend>
    <twig:Select>
        <option disabled selected>Pick a browser</option>
        <option>Chrome</option>
        <option>FireFox</option>
        <option>Safari</option>
    </twig:Select>
    <span class="label">Optional</span>
</fieldset>
```

### Primary Color

```twig {"preview":true}
<twig:Select color="primary">
    <option disabled selected>Pick a text editor</option>
    <option>VScode</option>
    <option>VScode fork</option>
    <option>Another VScode fork</option>
</twig:Select>
```

### Secondary Color

```twig {"preview":true}
<twig:Select color="secondary">
    <option disabled selected>Pick a language</option>
    <option>Zig</option>
    <option>Go</option>
    <option>Rust</option>
</twig:Select>
```

### Accent Color

```twig {"preview":true}
<twig:Select color="accent">
    <option disabled selected>Color scheme</option>
    <option>Light mode</option>
    <option>Dark mode</option>
    <option>System</option>
</twig:Select>
```

### Neutral Color

```twig {"preview":true}
<twig:Select color="neutral">
    <option disabled selected>Server location</option>
    <option>North America</option>
    <option>EU west</option>
    <option>South East Asia</option>
</twig:Select>
```

### Info Color

```twig {"preview":true}
<twig:Select color="info">
    <option disabled selected>Pick a Framework</option>
    <option>React</option>
    <option>Vue</option>
    <option>Angular</option>
</twig:Select>
```

### Success Color

```twig {"preview":true}
<twig:Select color="success">
    <option disabled selected>Pick a Runtime</option>
    <option>npm</option>
    <option>Bun</option>
    <option>yarn</option>
</twig:Select>
```

### Warning Color

```twig {"preview":true}
<twig:Select color="warning">
    <option disabled selected>Pick an OS</option>
    <option>Windows</option>
    <option>MacOS</option>
    <option>Linux</option>
</twig:Select>
```

### Error Color

```twig {"preview":true}
<twig:Select color="error">
    <option disabled selected>Pick an AI Model</option>
    <option>GPT-4</option>
    <option>Claude</option>
    <option>Llama</option>
</twig:Select>
```

### Sizes

```twig {"preview":true}
<div class="flex w-full flex-col items-center gap-4">
    <twig:Select size="xs">
        <option disabled selected>Xsmall</option>
        <option>Xsmall Apple</option>
        <option>Xsmall Orange</option>
        <option>Xsmall Tomato</option>
    </twig:Select>
    <twig:Select size="sm">
        <option disabled selected>Small</option>
        <option>Small Apple</option>
        <option>Small Orange</option>
        <option>Small Tomato</option>
    </twig:Select>
    <twig:Select>
        <option disabled selected>Medium</option>
        <option>Medium Apple</option>
        <option>Medium Orange</option>
        <option>Medium Tomato</option>
    </twig:Select>
    <twig:Select size="lg">
        <option disabled selected>Large</option>
        <option>Large Apple</option>
        <option>Large Orange</option>
        <option>Large Tomato</option>
    </twig:Select>
    <twig:Select size="xl">
        <option disabled selected>Xlarge</option>
        <option>Xlarge Apple</option>
        <option>Xlarge Orange</option>
        <option>Xlarge Tomato</option>
    </twig:Select>
</div>
```

### Disabled

```twig {"preview":true}
<twig:Select disabled>
    <option>You can't touch this</option>
</twig:Select>
```

### Using OS Native Style For The Options Dropdown

```twig {"preview":true}
<twig:Select class="appearance-none">
    <option disabled selected>Pick a color</option>
    <option>Crimson</option>
    <option>Amber</option>
    <option>Velvet</option>
</twig:Select>
```

### With Custom Dropdown Height

```twig {"preview":true}
<twig:Select class="[&::picker(select)]:max-h-26">
    <option disabled selected>Pick a color</option>
    <option>Crimson</option>
    <option>Amber</option>
    <option>Velvet</option>
</twig:Select>
```

### With Text Label Inside

Render the component as a `label` to put a text or an icon next to the select.

```twig {"preview":true}
<twig:Select as="label">
    <span class="label">Type</span>
    <select>
        <option>Personal</option>
        <option>Business</option>
    </select>
</twig:Select>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col gap-8">
    <div class="grid w-xs gap-4" dir="rtl">
        <twig:Select>
            <option disabled selected>اختر لونًا</option>
            <option>قرمزي</option>
            <option>كهرماني</option>
            <option>مخملي</option>
        </twig:Select>
        <twig:Select as="label" color="primary">
            <span class="label">النوع</span>
            <select>
                <option>شخصي</option>
                <option>عمل</option>
            </select>
        </twig:Select>
    </div>
    <div class="grid w-xs gap-4" dir="rtl">
        <twig:Select>
            <option disabled selected>בחר צבע</option>
            <option>ארגמן</option>
            <option>ענבר</option>
            <option>קטיפה</option>
        </twig:Select>
        <twig:Select as="label" color="primary">
            <span class="label">סוג</span>
            <select>
                <option>אישי</option>
                <option>עסקי</option>
            </select>
        </twig:Select>
    </div>
</div>
```

## API Reference

::: api-reference
