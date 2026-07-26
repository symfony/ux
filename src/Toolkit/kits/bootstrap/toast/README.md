# Toast

Show lightweight Bootstrap notifications with optional headers and dismissal controls.

```twig {"preview":true}
<div class="d-flex justify-content-center p-4">
    <twig:Toast :autohide="false">
        <twig:Toast:Header timestamp="11 mins ago">
            <span class="rounded me-2 bg-primary" style="width: 20px; height: 20px"></span>
            <strong class="me-auto">Bootstrap</strong>
        </twig:Toast:Header>
        <twig:Toast:Body>
            Hello, world! This is a toast message.
        </twig:Toast:Body>
    </twig:Toast>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Toast :autohide="false">
    <twig:Toast:Header title="Bootstrap" timestamp="just now" />
    <twig:Toast:Body>
        Your changes have been saved.
    </twig:Toast:Body>
</twig:Toast>
```

Bootstrap toasts are opt-in. Create an instance before showing a hidden toast:

```js
import { Toast } from 'bootstrap';

const trigger = document.querySelector('#live-toast-trigger');
const element = document.querySelector('#live-toast');
const toast = Toast.getOrCreateInstance(element);

trigger.addEventListener('click', () => toast.show());
```

A toast rendered with `show` is immediately visible and does not need JavaScript until it must hide, dismiss, or be shown again.

## Accessibility

Keep the live region in the DOM before injecting or updating a toast. Use the defaults `role="status"` and `live="polite"` for ordinary notifications; reserve `role="alert"` and `live="assertive"` for urgent interruptions.

Choose a delay long enough to read the message. Set `autohide` to `false` when a toast contains interactive controls and always provide an explicit close action in that case. Avoid placing important actions inside an autohiding toast because focus may be lost when it disappears.

## Examples

### Basic

Compose a standard toast from a header and body.

```twig {"preview":true}
<twig:Toast :autohide="false">
    <twig:Toast:Header timestamp="11 mins ago">
        <span class="rounded me-2 bg-primary" style="width: 20px; height: 20px"></span>
        <strong class="me-auto">Bootstrap</strong>
    </twig:Toast:Header>
    <twig:Toast:Body>
        Hello, world! This is a toast message.
    </twig:Toast:Body>
</twig:Toast>
```

### Live example

Start hidden and show the toast from application JavaScript.

```twig {"preview":true}
<button type="button" class="btn btn-primary mb-3" id="live-toast-trigger">
    Show live toast
</button>

<twig:Toast id="live-toast" :show="false">
    <twig:Toast:Header title="Bootstrap" timestamp="just now" />
    <twig:Toast:Body>
        See? Just like this.
    </twig:Toast:Body>
</twig:Toast>
```

### Translucent

Place the default translucent toast over colored content.

```twig {"preview":true}
<div class="p-4 rounded bg-primary bg-gradient">
    <twig:Toast :autohide="false">
        <twig:Toast:Header title="Bootstrap" timestamp="11 mins ago" />
        <twig:Toast:Body>
            Toasts are slightly translucent to blend with what is behind them.
        </twig:Toast:Body>
    </twig:Toast>
</div>
```

### Stacking

Stack multiple notifications inside a Bootstrap toast container.

```twig {"preview":true}
<div class="toast-container position-static">
    <twig:Toast :autohide="false">
        <twig:Toast:Header title="Bootstrap" timestamp="just now" />
        <twig:Toast:Body>
            First toast in the stack.
        </twig:Toast:Body>
    </twig:Toast>

    <twig:Toast :autohide="false">
        <twig:Toast:Header title="Bootstrap" timestamp="2 seconds ago" />
        <twig:Toast:Body>
            Second toast, automatically spaced by the container.
        </twig:Toast:Body>
    </twig:Toast>
</div>
```

### Custom content

Omit the header and compose body content, actions, and dismissal controls directly.

```twig {"preview":true}
<div class="d-flex flex-column gap-3">
    <twig:Toast :autohide="false" class="align-items-center">
        <div class="d-flex">
            <twig:Toast:Body>
                Hello, world! This is a toast message.
            </twig:Toast:Body>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </twig:Toast>

    <twig:Toast :autohide="false">
        <twig:Toast:Body>
            Hello, world! This is a toast message.
            <div class="mt-2 pt-2 border-top">
                <button type="button" class="btn btn-primary btn-sm">Take action</button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="toast">Close</button>
            </div>
        </twig:Toast:Body>
    </twig:Toast>
</div>
```

### Color schemes

Combine contextual background and foreground utilities for stronger visual emphasis.

```twig {"preview":true}
<div class="d-flex flex-column gap-3">
    {% for color in ['primary', 'success', 'danger'] %}
        <twig:Toast :autohide="false" class="text-bg-{{ color }} border-0">
            <div class="d-flex">
                <twig:Toast:Body>
                    A {{ color }} toast with matching contextual color.
                </twig:Toast:Body>
                <button type="button" class="btn-close me-2 m-auto" data-bs-theme="dark" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </twig:Toast>
    {% endfor %}
</div>
```

### Placement

Position a toast container with Bootstrap's positioning utilities.

```twig {"preview":true}
<div class="position-relative w-100 bg-body-tertiary border rounded" style="height: 320px">
    <div class="toast-container position-absolute top-0 end-0 p-3">
        <twig:Toast :autohide="false">
            <twig:Toast:Header title="Bootstrap" timestamp="just now" />
            <twig:Toast:Body>
                Position the container with Bootstrap utilities.
            </twig:Toast:Body>
        </twig:Toast>
    </div>
</div>
```

## API Reference

::: api-reference
