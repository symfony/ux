# Modal

Add accessible dialog overlays for notifications, forms, and custom content.

```twig {"preview":true}
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#demo-modal">
    Review changes
</button>

<twig:Modal id="demo-modal" centered>
    <twig:Modal:Header>
        <twig:Modal:Title>Review changes</twig:Modal:Title>
    </twig:Modal:Header>
    <twig:Modal:Body>
        <p>Your profile information is ready to be saved.</p>
        <p class="mb-0 text-body-secondary">You can return to editing or confirm these changes now.</p>
    </twig:Modal:Body>
    <twig:Modal:Footer>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep editing</button>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Save changes</button>
    </twig:Modal:Footer>
</twig:Modal>
```

## Installation

::: installation

## Usage

```twig
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#usage-modal">
    Open modal
</button>

<twig:Modal id="usage-modal">
    <twig:Modal:Header>
        <twig:Modal:Title>Modal title</twig:Modal:Title>
    </twig:Modal:Header>
    <twig:Modal:Body>Modal body content goes here.</twig:Modal:Body>
    <twig:Modal:Footer>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    </twig:Modal:Footer>
</twig:Modal>
```

## Accessibility

Include a `Modal:Title` so the root modal's `aria-labelledby` points to a real heading. A modal represents a separate document context, so an `h1` is usually appropriate; use the `tag` prop when the surrounding hierarchy requires another level.

Bootstrap adds the dialog role and manages focus while the modal is open. The HTML `autofocus` attribute does not focus controls inside a modal; listen for `shown.bs.modal` when an initial focus target is required. Do not nest modals, and always provide an explicit dismiss action.

## Examples

### Modal components

Display the complete modal structure statically when composing or reviewing its content.

```twig {"preview":true}
<twig:Modal id="static-modal" :fade="false" class="position-static d-block" aria-hidden="false">
    <twig:Modal:Header>
        <twig:Modal:Title tag="h5">Modal title</twig:Modal:Title>
    </twig:Modal:Header>
    <twig:Modal:Body>
        <p>Modal body text goes here.</p>
    </twig:Modal:Body>
    <twig:Modal:Footer>
        <button type="button" class="btn btn-secondary">Close</button>
        <button type="button" class="btn btn-primary">Save changes</button>
    </twig:Modal:Footer>
</twig:Modal>
```

### Live demo

Open and dismiss a standard animated modal through Bootstrap data attributes.

```twig {"preview":true}
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#live-demo-modal">
    Launch demo modal
</button>

<twig:Modal id="live-demo-modal">
    <twig:Modal:Header>
        <twig:Modal:Title>Modal title</twig:Modal:Title>
    </twig:Modal:Header>
    <twig:Modal:Body>
        <p>Woo-hoo, you're reading this text in a modal!</p>
    </twig:Modal:Body>
    <twig:Modal:Footer>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Save changes</button>
    </twig:Modal:Footer>
</twig:Modal>
```

### Static backdrop

Prevent backdrop clicks and Escape from dismissing a modal that requires an explicit choice.

```twig {"preview":true}
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#static-backdrop-modal">
    Launch static backdrop modal
</button>

<twig:Modal id="static-backdrop-modal" backdrop="static" :keyboard="false">
    <twig:Modal:Header>
        <twig:Modal:Title>Modal title</twig:Modal:Title>
    </twig:Modal:Header>
    <twig:Modal:Body>
        <p>I will not close if you click outside of me or press Escape.</p>
    </twig:Modal:Body>
    <twig:Modal:Footer>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Understood</button>
    </twig:Modal:Footer>
</twig:Modal>
```

### Scrolling long content

Let the viewport scroll with a long modal or constrain scrolling to the modal body.

```twig {"preview":true}
<div class="d-flex flex-wrap gap-2">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#long-content-modal">Launch long modal</button>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#scrollable-modal">Launch scrollable modal</button>
</div>

<twig:Modal id="long-content-modal">
    <twig:Modal:Header><twig:Modal:Title>Long modal</twig:Modal:Title></twig:Modal:Header>
    <twig:Modal:Body>
        <p>This modal grows with its content, allowing the viewport to scroll.</p>
        <div style="min-height: 900px;"></div>
        <p class="mb-0">This content appears at the bottom.</p>
    </twig:Modal:Body>
    <twig:Modal:Footer><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></twig:Modal:Footer>
</twig:Modal>

<twig:Modal id="scrollable-modal" scrollable>
    <twig:Modal:Header><twig:Modal:Title>Scrollable modal</twig:Modal:Title></twig:Modal:Header>
    <twig:Modal:Body>
        <p>Only this modal body scrolls when its content exceeds the available height.</p>
        <div style="min-height: 900px;"></div>
        <p class="mb-0">This content appears at the bottom.</p>
    </twig:Modal:Body>
    <twig:Modal:Footer><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></twig:Modal:Footer>
</twig:Modal>
```

### Vertically centered

Center the dialog vertically, with or without an independently scrollable body.

```twig {"preview":true}
<div class="d-flex flex-wrap gap-2">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#centered-modal">Vertically centered modal</button>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#centered-scrollable-modal">Centered scrollable modal</button>
</div>

<twig:Modal id="centered-modal" centered>
    <twig:Modal:Header><twig:Modal:Title>Modal title</twig:Modal:Title></twig:Modal:Header>
    <twig:Modal:Body>This is a vertically centered modal.</twig:Modal:Body>
    <twig:Modal:Footer><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></twig:Modal:Footer>
</twig:Modal>

<twig:Modal id="centered-scrollable-modal" centered scrollable>
    <twig:Modal:Header><twig:Modal:Title>Modal title</twig:Modal:Title></twig:Modal:Header>
    <twig:Modal:Body>
        <p>This modal combines vertical centering with an independently scrollable body.</p>
        <div style="min-height: 900px;"></div>
        <p class="mb-0">Just like that.</p>
    </twig:Modal:Body>
    <twig:Modal:Footer><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></twig:Modal:Footer>
</twig:Modal>
```

### Tooltips and popovers

Place supplementary Bootstrap overlays inside a modal. Bootstrap dismisses them automatically when the modal closes.

```twig {"preview":true}
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-with-overlays">
    Launch demo modal
</button>

<twig:Modal id="modal-with-overlays">
    <twig:Modal:Header>
        <twig:Modal:Title>Modal title</twig:Modal:Title>
    </twig:Modal:Header>
    <twig:Modal:Body>
        <h2 class="fs-5">Popover in a modal</h2>
        <p>
            This
            <twig:Popover title="Popover title" content="Popover body content is set in this attribute.">
                <button class="btn btn-secondary" type="button" {{ html_attr(popover_trigger_attrs) }}>button</button>
            </twig:Popover>
            triggers a popover on click.
        </p>
        <hr>
        <h2 class="fs-5">Tooltips in a modal</h2>
        <p>
            <twig:Tooltip title="Tooltip"><a href="#" {{ html_attr(tooltip_trigger_attrs) }}>This link</a></twig:Tooltip>
            and
            <twig:Tooltip title="Tooltip"><a href="#" {{ html_attr(tooltip_trigger_attrs) }}>that link</a></twig:Tooltip>
            have tooltips on hover.
        </p>
    </twig:Modal:Body>
    <twig:Modal:Footer>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Save changes</button>
    </twig:Modal:Footer>
</twig:Modal>
```

### Using the grid

Nest Bootstrap's responsive grid inside the modal body.

```twig {"preview":true}
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#grid-modal">
    Launch grid modal
</button>

<twig:Modal id="grid-modal" size="lg">
    <twig:Modal:Header><twig:Modal:Title>Grids in modals</twig:Modal:Title></twig:Modal:Header>
    <twig:Modal:Body>
        <div class="container-fluid text-center">
            <div class="row mb-3">
                <div class="col-md-4 border py-2">.col-md-4</div>
                <div class="col-md-4 ms-auto border py-2">.col-md-4 .ms-auto</div>
            </div>
            <div class="row mb-3">
                <div class="col-md-3 ms-auto border py-2">.col-md-3 .ms-auto</div>
                <div class="col-md-2 ms-auto border py-2">.col-md-2 .ms-auto</div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6 ms-auto border py-2">.col-md-6 .ms-auto</div>
            </div>
            <div class="row">
                <div class="col-sm-9 border py-2">
                    Level 1: .col-sm-9
                    <div class="row mt-2">
                        <div class="col-8 col-sm-6 border py-2">Level 2: .col-8 .col-sm-6</div>
                        <div class="col-4 col-sm-6 border py-2">Level 2: .col-4 .col-sm-6</div>
                    </div>
                </div>
            </div>
        </div>
    </twig:Modal:Body>
    <twig:Modal:Footer><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></twig:Modal:Footer>
</twig:Modal>
```

### Varying modal content

Read data attributes from the triggering button to update a shared modal before it is displayed.

```twig {"preview":true}
<div class="d-flex flex-wrap gap-2">
    {% for recipient in ['@mdo', '@fat', '@getbootstrap'] %}
        <button
            type="button"
            class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#varying-content-modal"
            data-bs-recipient="{{ recipient }}"
        >Open modal for {{ recipient }}</button>
    {% endfor %}
</div>

<twig:Modal id="varying-content-modal">
    <twig:Modal:Header>
        <twig:Modal:Title>New message</twig:Modal:Title>
    </twig:Modal:Header>
    <twig:Modal:Body>
        <form>
            <div class="mb-3">
                <label for="recipient-name" class="col-form-label">Recipient:</label>
                <input type="text" class="form-control" id="recipient-name">
            </div>
            <div class="mb-3">
                <label for="message-text" class="col-form-label">Message:</label>
                <textarea class="form-control" id="message-text"></textarea>
            </div>
        </form>
    </twig:Modal:Body>
    <twig:Modal:Footer>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Send message</button>
    </twig:Modal:Footer>
</twig:Modal>

<script type="module">
    const modal = document.querySelector('#varying-content-modal');

    modal.addEventListener('show.bs.modal', (event) => {
        const recipient = event.relatedTarget.getAttribute('data-bs-recipient');
        modal.querySelector('.modal-title').textContent = `New message to ${recipient}`;
        modal.querySelector('#recipient-name').value = recipient;
    });
</script>
```

### Toggle between modals

Switch between two modals without displaying them at the same time.

```twig {"preview":true}
<button class="btn btn-primary" data-bs-target="#first-toggle-modal" data-bs-toggle="modal">Open first modal</button>

<twig:Modal id="first-toggle-modal" centered>
    <twig:Modal:Header><twig:Modal:Title>Modal 1</twig:Modal:Title></twig:Modal:Header>
    <twig:Modal:Body>Show a second modal and hide this one with the button below.</twig:Modal:Body>
    <twig:Modal:Footer>
        <button class="btn btn-primary" data-bs-target="#second-toggle-modal" data-bs-toggle="modal">Open second modal</button>
    </twig:Modal:Footer>
</twig:Modal>

<twig:Modal id="second-toggle-modal" centered>
    <twig:Modal:Header><twig:Modal:Title>Modal 2</twig:Modal:Title></twig:Modal:Header>
    <twig:Modal:Body>Hide this modal and show the first with the button below.</twig:Modal:Body>
    <twig:Modal:Footer>
        <button class="btn btn-primary" data-bs-target="#first-toggle-modal" data-bs-toggle="modal">Back to first</button>
    </twig:Modal:Footer>
</twig:Modal>
```

### Remove animation

Disable the fade transition when the modal should appear immediately.

```twig {"preview":true}
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#no-animation-modal">
    Open without animation
</button>

<twig:Modal id="no-animation-modal" :fade="false">
    <twig:Modal:Header><twig:Modal:Title>Modal without animation</twig:Modal:Title></twig:Modal:Header>
    <twig:Modal:Body>This modal appears immediately because the <code>.fade</code> class is omitted.</twig:Modal:Body>
    <twig:Modal:Footer><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></twig:Modal:Footer>
</twig:Modal>
```

### Optional sizes

Choose Bootstrap's small, large, or extra-large modal widths.

```twig {"preview":true}
<div class="d-flex flex-wrap gap-2">
    {% for size, label in {xl: 'Extra large', lg: 'Large', sm: 'Small'} %}
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#size-{{ size }}-modal">{{ label }} modal</button>
    {% endfor %}
</div>

{% for size, label in {xl: 'Extra large', lg: 'Large', sm: 'Small'} %}
    <twig:Modal id="size-{{ size }}-modal" size="{{ size }}">
        <twig:Modal:Header><twig:Modal:Title>{{ label }} modal</twig:Modal:Title></twig:Modal:Header>
        <twig:Modal:Body>Modal content at the <code>{{ size }}</code> size.</twig:Modal:Body>
        <twig:Modal:Footer><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></twig:Modal:Footer>
    </twig:Modal>
{% endfor %}
```

### Fullscreen modal

Fill the viewport at every size or only below a selected breakpoint.

```twig {"preview":true}
{% set variants = {
    always: ['Full screen', true],
    sm: ['Full screen below sm', 'sm'],
    md: ['Full screen below md', 'md'],
    lg: ['Full screen below lg', 'lg'],
    xl: ['Full screen below xl', 'xl'],
    xxl: ['Full screen below xxl', 'xxl'],
} %}

<div class="d-flex flex-wrap gap-2">
    {% for key, variant in variants %}
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#fullscreen-{{ key }}-modal">{{ variant[0] }}</button>
    {% endfor %}
</div>

{% for key, variant in variants %}
    <twig:Modal id="fullscreen-{{ key }}-modal" fullscreen="{{ variant[1] }}">
        <twig:Modal:Header><twig:Modal:Title>{{ variant[0] }}</twig:Modal:Title></twig:Modal:Header>
        <twig:Modal:Body>Responsive fullscreen modal content.</twig:Modal:Body>
        <twig:Modal:Footer><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></twig:Modal:Footer>
    </twig:Modal>
{% endfor %}
```

## API Reference

::: api-reference
