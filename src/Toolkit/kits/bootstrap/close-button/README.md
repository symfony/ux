# Close Button

Provide an accessible control for dismissing content such as modals and alerts.

```twig {"preview":true}
<div class="d-flex align-items-center gap-4">
    <twig:CloseButton label="Dismiss notification" />
    <twig:CloseButton disabled label="Dismiss notification" />
    <span class="rounded bg-dark p-3" data-bs-theme="dark">
        <twig:CloseButton label="Dismiss dark notification" />
    </span>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:CloseButton />
```

## Accessibility

Always provide a concise `label` that identifies what the button closes or dismisses. The visible icon is decorative and the accessible name comes from `aria-label`.

Use the disabled state only when the close action is genuinely unavailable.

## Examples

Render a standard close button with an accessible label.

```twig {"preview":true}
<twig:CloseButton label="Close" />
```

### Disabled state

Disable the button when the dismiss action is unavailable.

```twig {"preview":true}
<twig:CloseButton disabled label="Close" />
```

### Dark variant

Use Bootstrap's `data-bs-theme="dark"` color mode on the button or an ancestor. The deprecated `.btn-close-white` class is intentionally not used.

```twig {"preview":true}
<div class="d-inline-flex gap-3 rounded bg-dark p-3" data-bs-theme="dark">
    <twig:CloseButton label="Close" />
    <twig:CloseButton disabled label="Close" />
</div>
```

## API Reference

::: api-reference
