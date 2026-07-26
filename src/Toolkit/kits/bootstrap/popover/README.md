# Popover

Display contextual Bootstrap content beside a trigger element.

```twig {"preview":true}
<div class="d-flex w-100">
    <twig:Popover title="Bootstrap popover" content="This content appears beside the trigger.">
        <button type="button" class="btn btn-primary" {{ html_attr(popover_trigger_attrs) }}>
            Toggle popover
        </button>
    </twig:Popover>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Popover title="Popover title" content="Helpful contextual information.">
    <button type="button" class="btn btn-secondary" {{ html_attr(popover_trigger_attrs) }}>
        Show popover
    </button>
</twig:Popover>
```

Bootstrap popovers are opt-in. Initialize them after Bootstrap is loaded:

```js
import { Popover } from 'bootstrap';

document.querySelectorAll('[data-bs-toggle="popover"]').forEach((element) => {
    Popover.getOrCreateInstance(element);
});
```

The component follows an as-child pattern: render `popover_trigger_attrs` with Twig's `html_attr()` function on the actual interactive element so Bootstrap receives the data attributes without an extra wrapper.

## Accessibility

Use a button for popovers opened by click. For a dismiss-on-next-click popover, use the `focus` trigger on an element that can receive keyboard focus. Never rely on `hover` alone because keyboard and touch users cannot reliably reach that content.

Keep popovers short and supplementary. Their generated markup is not a modal dialog and does not trap focus. Disabled controls cannot receive focus, so attach the popover to a focusable wrapper and keep the disabled control inside it.

When `html` is enabled, Bootstrap sanitizes allowed markup by default. Do not disable sanitization for untrusted content.

## Examples

### Enable popovers

Initialize every `data-bs-toggle="popover"` trigger before expecting it to open.

```twig {"preview":true}
<div class="d-flex w-100">
    <twig:Popover content="Popover components are opt-in and must be initialized in JavaScript.">
        <button type="button" class="btn btn-secondary" {{ html_attr(popover_trigger_attrs) }}>
            Popover trigger
        </button>
    </twig:Popover>
</div>
```

### Live demo

Combine a heading and body content in a conventional click-triggered popover.

```twig {"preview":true}
<div class="d-flex w-100">
    <twig:Popover
        title="Popover title"
        content="And here's some amazing content. It's very engaging. Right?"
    >
        <button type="button" class="btn btn-lg btn-danger" {{ html_attr(popover_trigger_attrs) }}>
            Click to toggle popover
        </button>
    </twig:Popover>
</div>
```

### Four directions

Prefer top, right, bottom, or left placement while allowing Popper to adjust when space is constrained.

```twig {"preview":true}
<div class="d-flex flex-wrap justify-content-center gap-2 p-5">
    {% for placement in ['left', 'top', 'bottom', 'right'] %}
        <twig:Popover
            title="Popover {{ placement }}"
            content="On the {{ placement }} side."
            :placement="placement"
        >
            <button type="button" class="btn btn-secondary text-capitalize" {{ html_attr(popover_trigger_attrs) }}>
                {{ placement }}
            </button>
        </twig:Popover>
    {% endfor %}
</div>
```

### Custom container

Append the generated popover to a specific container when the surrounding layout requires it.

```twig {"preview":true}
<div id="popover-container" class="border rounded p-5 text-center w-100">
    <twig:Popover
        title="Contained popover"
        content="Bootstrap appends this popover inside the selected container."
        container="#popover-container"
    >
        <button type="button" class="btn btn-secondary" {{ html_attr(popover_trigger_attrs) }}>
            Use custom container
        </button>
    </twig:Popover>
</div>
```

### Custom popovers

Attach a custom class to the generated popover and customize Bootstrap's CSS variables in your stylesheet.

```twig {"preview":true}
<div class="d-flex w-100">
    <twig:Popover
        title="Custom popover"
        content="Use the generated class to customize Bootstrap CSS variables."
        customClass="custom-popover"
    >
        <button type="button" class="btn btn-secondary" {{ html_attr(popover_trigger_attrs) }}>
            Custom popover
        </button>
    </twig:Popover>
</div>
```

### Dismiss on next click

Use the `focus` trigger so moving focus away closes the popover.

```twig {"preview":true}
<div class="d-flex w-100">
    <twig:Popover
        title="Dismissible popover"
        content="Move focus to another element to dismiss this popover."
        trigger="focus"
    >
        <a class="btn btn-lg btn-danger" href="#" role="button" tabindex="0" {{ html_attr(popover_trigger_attrs) }}>
            Dismissible popover
        </a>
    </twig:Popover>
</div>
```

### Disabled elements

Place a disabled control inside a focusable wrapper that owns the popover attributes.

```twig {"preview":true}
<div class="d-flex w-100">
    <twig:Popover
        content="Disabled controls cannot receive focus, so the wrapper owns the popover."
        trigger="hover focus"
    >
        <span class="d-inline-block" tabindex="0" {{ html_attr(popover_trigger_attrs) }}>
            <button class="btn btn-primary" type="button" disabled>Disabled button</button>
        </span>
    </twig:Popover>
</div>
```

## API Reference

::: api-reference
