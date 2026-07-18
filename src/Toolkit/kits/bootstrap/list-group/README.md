# List Group

Display a flexible series of content with active, disabled, actionable, and contextual states.

```twig {"preview":true,"height":"270px"}
<twig:ListGroup tag="div" style="max-width: 28rem;">
    <twig:ListGroup:Item href="#" active>The current link item</twig:ListGroup:Item>
    <twig:ListGroup:Item href="#">A second link item</twig:ListGroup:Item>
    <twig:ListGroup:Item href="#">A third link item</twig:ListGroup:Item>
    <twig:ListGroup:Item href="#" disabled>A disabled link item</twig:ListGroup:Item>
</twig:ListGroup>
```

## Installation

::: installation

## Usage

```twig
<twig:ListGroup>
    <twig:ListGroup:Item>An item</twig:ListGroup:Item>
    <twig:ListGroup:Item>A second item</twig:ListGroup:Item>
    <twig:ListGroup:Item active>A third item</twig:ListGroup:Item>
</twig:ListGroup>
```

## Accessibility

Do not rely on contextual color alone to communicate meaning. Make the meaning explicit in the visible content or provide additional visually hidden text.

Disabled links omit their `href`, expose `aria-disabled="true"`, and are removed from keyboard navigation. Associate every checkbox and radio with a visible label or an `aria-label`.

## Examples

### Basic example

Use a list group to display a simple series of related items.

```twig {"preview":true,"height":"310px"}
<twig:ListGroup>
    <twig:ListGroup:Item>An item</twig:ListGroup:Item>
    <twig:ListGroup:Item>A second item</twig:ListGroup:Item>
    <twig:ListGroup:Item>A third item</twig:ListGroup:Item>
    <twig:ListGroup:Item>A fourth item</twig:ListGroup:Item>
    <twig:ListGroup:Item>And a fifth one</twig:ListGroup:Item>
</twig:ListGroup>
```

### Active items

Mark the current selection with the active state.

```twig {"preview":true,"height":"310px"}
<twig:ListGroup>
    <twig:ListGroup:Item active>An active item</twig:ListGroup:Item>
    <twig:ListGroup:Item>A second item</twig:ListGroup:Item>
    <twig:ListGroup:Item>A third item</twig:ListGroup:Item>
    <twig:ListGroup:Item>A fourth item</twig:ListGroup:Item>
    <twig:ListGroup:Item>And a fifth one</twig:ListGroup:Item>
</twig:ListGroup>
```

### Links and buttons

Render actionable list items as links or buttons to expose hover, active, and disabled states.

```twig {"preview":true,"height":"530px"}
<div class="vstack gap-3">
    <twig:ListGroup tag="div">
        <twig:ListGroup:Item href="#" active>The current link item</twig:ListGroup:Item>
        <twig:ListGroup:Item href="#">A second link item</twig:ListGroup:Item>
        <twig:ListGroup:Item href="#">A third link item</twig:ListGroup:Item>
        <twig:ListGroup:Item href="#">A fourth link item</twig:ListGroup:Item>
        <twig:ListGroup:Item href="#" disabled>A disabled link item</twig:ListGroup:Item>
    </twig:ListGroup>

    <twig:ListGroup tag="div">
        <twig:ListGroup:Item tag="button" active>The current button</twig:ListGroup:Item>
        <twig:ListGroup:Item tag="button">A second button item</twig:ListGroup:Item>
        <twig:ListGroup:Item tag="button">A third button item</twig:ListGroup:Item>
        <twig:ListGroup:Item tag="button">A fourth button item</twig:ListGroup:Item>
        <twig:ListGroup:Item tag="button" disabled>A disabled button item</twig:ListGroup:Item>
    </twig:ListGroup>
</div>
```

### Flush

Remove outer borders and rounded corners for edge-to-edge rendering inside a parent container.

```twig {"preview":true,"height":"310px"}
<twig:ListGroup flush>
    <twig:ListGroup:Item>An item</twig:ListGroup:Item>
    <twig:ListGroup:Item>A second item</twig:ListGroup:Item>
    <twig:ListGroup:Item>A third item</twig:ListGroup:Item>
    <twig:ListGroup:Item>A fourth item</twig:ListGroup:Item>
    <twig:ListGroup:Item>And a fifth one</twig:ListGroup:Item>
</twig:ListGroup>
```

### Numbered

Generate item numbers with Bootstrap's CSS counters, including for items with custom content.

```twig {"preview":true,"height":"440px"}
<div class="vstack gap-3">
    <twig:ListGroup numbered>
        <twig:ListGroup:Item>A list item</twig:ListGroup:Item>
        <twig:ListGroup:Item>A list item</twig:ListGroup:Item>
        <twig:ListGroup:Item>A list item</twig:ListGroup:Item>
    </twig:ListGroup>

    <twig:ListGroup numbered>
        {% for item in 1..3 %}
            <twig:ListGroup:Item class="d-flex justify-content-between align-items-start">
                <div class="ms-2 me-auto">
                    <div class="fw-bold">Subheading</div>
                    Content for list item
                </div>
                <span class="badge text-bg-primary rounded-pill">14</span>
            </twig:ListGroup:Item>
        {% endfor %}
    </twig:ListGroup>
</div>
```

### Horizontal

Display items horizontally at every breakpoint or from a selected responsive breakpoint.

```twig {"preview":true,"height":"720px"}
<div class="vstack gap-2">
    {% for breakpoint in [true, 'sm', 'md', 'lg', 'xl', 'xxl'] %}
        <twig:ListGroup :horizontal="breakpoint">
            <twig:ListGroup:Item>An item</twig:ListGroup:Item>
            <twig:ListGroup:Item>A second item</twig:ListGroup:Item>
            <twig:ListGroup:Item>A third item</twig:ListGroup:Item>
        </twig:ListGroup>
    {% endfor %}
</div>
```

### Variants

Apply contextual colors to non-actionable list items.

```twig {"preview":true,"height":"470px"}
<twig:ListGroup>
    <twig:ListGroup:Item>A simple default list group item</twig:ListGroup:Item>
    <twig:ListGroup:Item color="primary">A simple primary list group item</twig:ListGroup:Item>
    <twig:ListGroup:Item color="secondary">A simple secondary list group item</twig:ListGroup:Item>
    <twig:ListGroup:Item color="success">A simple success list group item</twig:ListGroup:Item>
    <twig:ListGroup:Item color="danger">A simple danger list group item</twig:ListGroup:Item>
    <twig:ListGroup:Item color="warning">A simple warning list group item</twig:ListGroup:Item>
    <twig:ListGroup:Item color="info">A simple info list group item</twig:ListGroup:Item>
    <twig:ListGroup:Item color="light">A simple light list group item</twig:ListGroup:Item>
    <twig:ListGroup:Item color="dark">A simple dark list group item</twig:ListGroup:Item>
</twig:ListGroup>
```

### Action variants

Combine contextual colors with actionable links and their hover and active states.

```twig {"preview":true,"height":"470px"}
<twig:ListGroup tag="div">
    <twig:ListGroup:Item href="#">A simple default list group item</twig:ListGroup:Item>
    <twig:ListGroup:Item href="#" color="primary">A simple primary list group item</twig:ListGroup:Item>
    <twig:ListGroup:Item href="#" color="secondary">A simple secondary list group item</twig:ListGroup:Item>
    <twig:ListGroup:Item href="#" color="success">A simple success list group item</twig:ListGroup:Item>
    <twig:ListGroup:Item href="#" color="danger">A simple danger list group item</twig:ListGroup:Item>
    <twig:ListGroup:Item href="#" color="warning">A simple warning list group item</twig:ListGroup:Item>
    <twig:ListGroup:Item href="#" color="info">A simple info list group item</twig:ListGroup:Item>
    <twig:ListGroup:Item href="#" color="light">A simple light list group item</twig:ListGroup:Item>
    <twig:ListGroup:Item href="#" color="dark">A simple dark list group item</twig:ListGroup:Item>
</twig:ListGroup>
```

### With badges

Add badges to communicate counts or activity alongside an item label.

```twig {"preview":true,"height":"230px"}
<twig:ListGroup>
    <twig:ListGroup:Item class="d-flex justify-content-between align-items-center">
        A list item
        <span class="badge text-bg-primary rounded-pill">14</span>
    </twig:ListGroup:Item>
    <twig:ListGroup:Item class="d-flex justify-content-between align-items-center">
        A second list item
        <span class="badge text-bg-primary rounded-pill">2</span>
    </twig:ListGroup:Item>
    <twig:ListGroup:Item class="d-flex justify-content-between align-items-center">
        A third list item
        <span class="badge text-bg-primary rounded-pill">1</span>
    </twig:ListGroup:Item>
</twig:ListGroup>
```

### Custom content

Compose headings, supporting text, metadata, and other HTML within actionable items.

```twig {"preview":true,"height":"380px"}
<twig:ListGroup tag="div">
    <twig:ListGroup:Item href="#" active>
        <div class="d-flex w-100 justify-content-between">
            <h5 class="mb-1">List group item heading</h5>
            <small>3 days ago</small>
        </div>
        <p class="mb-1">Some placeholder content in a paragraph.</p>
        <small>And some small print.</small>
    </twig:ListGroup:Item>
    {% for item in 1..2 %}
        <twig:ListGroup:Item href="#">
            <div class="d-flex w-100 justify-content-between">
                <h5 class="mb-1">List group item heading</h5>
                <small class="text-body-secondary">3 days ago</small>
            </div>
            <p class="mb-1">Some placeholder content in a paragraph.</p>
            <small class="text-body-secondary">And some muted small print.</small>
        </twig:ListGroup:Item>
    {% endfor %}
</twig:ListGroup>
```

### Checkboxes and radios

Place labeled form controls inside list items, with an optional stretched label for a larger click target.

```twig {"preview":true,"height":"510px"}
<div class="vstack gap-3">
    <twig:ListGroup>
        <twig:ListGroup:Item>
            <input class="form-check-input me-1" type="checkbox" id="firstCheckbox">
            <label class="form-check-label" for="firstCheckbox">First checkbox</label>
        </twig:ListGroup:Item>
        <twig:ListGroup:Item>
            <input class="form-check-input me-1" type="checkbox" id="secondCheckbox">
            <label class="form-check-label" for="secondCheckbox">Second checkbox</label>
        </twig:ListGroup:Item>
        <twig:ListGroup:Item>
            <input class="form-check-input me-1" type="checkbox" id="thirdCheckbox">
            <label class="form-check-label" for="thirdCheckbox">Third checkbox</label>
        </twig:ListGroup:Item>
    </twig:ListGroup>

    <twig:ListGroup>
        <twig:ListGroup:Item>
            <input class="form-check-input me-1" type="radio" name="listGroupRadio" id="firstRadio" checked>
            <label class="form-check-label" for="firstRadio">First radio</label>
        </twig:ListGroup:Item>
        <twig:ListGroup:Item>
            <input class="form-check-input me-1" type="radio" name="listGroupRadio" id="secondRadio">
            <label class="form-check-label" for="secondRadio">Second radio</label>
        </twig:ListGroup:Item>
        <twig:ListGroup:Item>
            <input class="form-check-input me-1" type="radio" name="listGroupRadio" id="thirdRadio">
            <label class="form-check-label" for="thirdRadio">Third radio</label>
        </twig:ListGroup:Item>
    </twig:ListGroup>

    <twig:ListGroup>
        <twig:ListGroup:Item>
            <input class="form-check-input me-1" type="checkbox" id="firstCheckboxStretched">
            <label class="form-check-label stretched-link" for="firstCheckboxStretched">First checkbox</label>
        </twig:ListGroup:Item>
        <twig:ListGroup:Item>
            <input class="form-check-input me-1" type="checkbox" id="secondCheckboxStretched">
            <label class="form-check-label stretched-link" for="secondCheckboxStretched">Second checkbox</label>
        </twig:ListGroup:Item>
        <twig:ListGroup:Item>
            <input class="form-check-input me-1" type="checkbox" id="thirdCheckboxStretched">
            <label class="form-check-label stretched-link" for="thirdCheckboxStretched">Third checkbox</label>
        </twig:ListGroup:Item>
    </twig:ListGroup>
</div>
```

### JavaScript behavior

Use Bootstrap's tab plugin and data attributes to connect list items with tab panels.

```twig {"preview":true,"height":"270px"}
<div class="row">
    <div class="col-4">
        <twig:ListGroup tag="div" id="list-tab" role="tablist">
            <twig:ListGroup:Item
                href="#list-home"
                id="list-home-list"
                role="tab"
                data-bs-toggle="list"
                aria-controls="list-home"
                :current="false"
                active
            >Home</twig:ListGroup:Item>
            <twig:ListGroup:Item
                href="#list-profile"
                id="list-profile-list"
                role="tab"
                data-bs-toggle="list"
                aria-controls="list-profile"
            >Profile</twig:ListGroup:Item>
            <twig:ListGroup:Item
                href="#list-messages"
                id="list-messages-list"
                role="tab"
                data-bs-toggle="list"
                aria-controls="list-messages"
            >Messages</twig:ListGroup:Item>
            <twig:ListGroup:Item
                href="#list-settings"
                id="list-settings-list"
                role="tab"
                data-bs-toggle="list"
                aria-controls="list-settings"
            >Settings</twig:ListGroup:Item>
        </twig:ListGroup>
    </div>
    <div class="col-8">
        <div class="tab-content" id="list-tab-content">
            <div class="tab-pane fade show active" id="list-home" role="tabpanel" aria-labelledby="list-home-list" tabindex="0">
                Some placeholder content relating to Home.
            </div>
            <div class="tab-pane fade" id="list-profile" role="tabpanel" aria-labelledby="list-profile-list" tabindex="0">
                Some placeholder content relating to Profile.
            </div>
            <div class="tab-pane fade" id="list-messages" role="tabpanel" aria-labelledby="list-messages-list" tabindex="0">
                Some placeholder content relating to Messages.
            </div>
            <div class="tab-pane fade" id="list-settings" role="tabpanel" aria-labelledby="list-settings-list" tabindex="0">
                Some placeholder content relating to Settings.
            </div>
        </div>
    </div>
</div>
```

## API Reference

::: api-reference
