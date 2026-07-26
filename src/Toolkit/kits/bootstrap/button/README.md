# Button

Use Bootstrap button styles for actions in forms, dialogs, navigation, and more.

```twig {"preview":true}
<div class="d-flex flex-wrap gap-2">
    <twig:Button>Primary</twig:Button>
    <twig:Button color="secondary">Secondary</twig:Button>
    <twig:Button color="success">Success</twig:Button>
    <twig:Button color="danger">Danger</twig:Button>
    <twig:Button color="warning">Warning</twig:Button>
    <twig:Button color="info">Info</twig:Button>
    <twig:Button color="light">Light</twig:Button>
    <twig:Button color="dark">Dark</twig:Button>
    <twig:Button color="link">Link</twig:Button>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Button>Save changes</twig:Button>
```

## Accessibility

Do not rely on button color alone to communicate meaning. Use explicit labels or additional visually hidden text when needed.

Disabled links omit their `href`, expose `aria-disabled="true"`, and are removed from keyboard navigation.

## Examples

### Base class

Use the base Bootstrap button class without a contextual color when defining a custom style.

```twig {"preview":true}
<twig:Button :color="null">Base class</twig:Button>
```

### Variants

Use contextual colors to communicate the purpose of an action.

```twig {"preview":true}
<div class="d-flex flex-wrap gap-2">
    <twig:Button>Primary</twig:Button>
    <twig:Button color="secondary">Secondary</twig:Button>
    <twig:Button color="success">Success</twig:Button>
    <twig:Button color="danger">Danger</twig:Button>
    <twig:Button color="warning">Warning</twig:Button>
    <twig:Button color="info">Info</twig:Button>
    <twig:Button color="light">Light</twig:Button>
    <twig:Button color="dark">Dark</twig:Button>
    <twig:Button color="link">Link</twig:Button>
</div>
```

### Disable text wrapping

Add Bootstrap's `text-nowrap` utility when a button label must remain on one line.

```twig {"preview":true}
<twig:Button class="text-nowrap">This button label does not wrap</twig:Button>
```

### Button tags

Render the component as a button, link, or input depending on the semantic element required.

```twig {"preview":true}
<div class="d-flex flex-wrap gap-2">
    <twig:Button tag="a" href="#">Link</twig:Button>
    <twig:Button type="submit">Button</twig:Button>
    <twig:Button tag="input" value="Input" />
    <twig:Button tag="input" type="submit" value="Submit" />
    <twig:Button tag="input" type="reset" value="Reset" />
</div>
```

### Outline buttons

Use outline styles for actions that need less visual weight.

```twig {"preview":true}
<div class="d-flex flex-wrap gap-2">
    <twig:Button outline>Primary</twig:Button>
    <twig:Button color="secondary" outline>Secondary</twig:Button>
    <twig:Button color="success" outline>Success</twig:Button>
    <twig:Button color="danger" outline>Danger</twig:Button>
    <twig:Button color="warning" outline>Warning</twig:Button>
    <twig:Button color="info" outline>Info</twig:Button>
    <twig:Button color="light" outline>Light</twig:Button>
    <twig:Button color="dark" outline>Dark</twig:Button>
</div>
```

### Sizes

Use Bootstrap's large and small sizes, or customize the component with Bootstrap CSS variables.

```twig {"preview":true}
<div class="d-flex flex-column align-items-start gap-3">
    <div class="d-flex flex-wrap gap-2">
        <twig:Button size="lg">Large button</twig:Button>
        <twig:Button color="secondary" size="lg">Large button</twig:Button>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <twig:Button size="sm">Small button</twig:Button>
        <twig:Button color="secondary" size="sm">Small button</twig:Button>
    </div>
    <twig:Button style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .75rem;">
        Custom button
    </twig:Button>
</div>
```

### Disabled state

Disable buttons and links while preserving the appropriate HTML and accessibility semantics.

```twig {"preview":true}
<div class="d-flex flex-column align-items-start gap-3">
    <div class="d-flex flex-wrap gap-2">
        <twig:Button disabled>Primary button</twig:Button>
        <twig:Button color="secondary" disabled>Button</twig:Button>
        <twig:Button outline disabled>Primary button</twig:Button>
        <twig:Button color="secondary" outline disabled>Button</twig:Button>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <twig:Button tag="a" disabled>Primary link</twig:Button>
        <twig:Button tag="a" color="secondary" disabled>Link</twig:Button>
    </div>
</div>
```

### Block buttons

Combine the component with Bootstrap layout utilities to create responsive full-width buttons.

```twig {"preview":true}
<div class="d-flex flex-column gap-4">
    <div class="d-grid gap-2">
        <twig:Button>Button</twig:Button>
        <twig:Button>Button</twig:Button>
    </div>
    <div class="d-grid gap-2 d-md-block">
        <twig:Button>Button</twig:Button>
        <twig:Button>Button</twig:Button>
    </div>
    <div class="d-grid gap-2 col-6 mx-auto">
        <twig:Button>Button</twig:Button>
        <twig:Button>Button</twig:Button>
    </div>
    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
        <twig:Button class="me-md-2">Button</twig:Button>
        <twig:Button>Button</twig:Button>
    </div>
</div>
```

### Toggle states

Use Bootstrap's button plugin for controls that toggle between pressed and unpressed states.

```twig {"preview":true}
<div class="d-flex flex-column align-items-start gap-3">
    <div class="d-inline-flex flex-wrap gap-1">
        <twig:Button :color="null" data-bs-toggle="button">Toggle button</twig:Button>
        <twig:Button :color="null" class="active" data-bs-toggle="button" aria-pressed="true">Active toggle button</twig:Button>
        <twig:Button :color="null" disabled data-bs-toggle="button">Disabled toggle button</twig:Button>
    </div>
    <div class="d-inline-flex flex-wrap gap-1">
        <twig:Button data-bs-toggle="button">Toggle button</twig:Button>
        <twig:Button class="active" data-bs-toggle="button" aria-pressed="true">Active toggle button</twig:Button>
        <twig:Button disabled data-bs-toggle="button">Disabled toggle button</twig:Button>
    </div>
    <div class="d-inline-flex flex-wrap gap-1">
        <twig:Button tag="a" :color="null" href="#" data-bs-toggle="button">Toggle link</twig:Button>
        <twig:Button tag="a" :color="null" href="#" class="active" data-bs-toggle="button" aria-pressed="true">Active toggle link</twig:Button>
        <twig:Button tag="a" :color="null" disabled data-bs-toggle="button">Disabled toggle link</twig:Button>
    </div>
    <div class="d-inline-flex flex-wrap gap-1">
        <twig:Button tag="a" href="#" data-bs-toggle="button">Toggle link</twig:Button>
        <twig:Button tag="a" href="#" class="active" data-bs-toggle="button" aria-pressed="true">Active toggle link</twig:Button>
        <twig:Button tag="a" disabled data-bs-toggle="button">Disabled toggle link</twig:Button>
    </div>
</div>
```

## API Reference

::: api-reference
