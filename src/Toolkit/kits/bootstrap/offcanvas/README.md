# Offcanvas

Build responsive sliding panels for navigation, forms, and supplementary content.

```twig {"preview":true}
<div class="d-flex justify-content-center w-100">
    <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#demo-offcanvas" aria-controls="demo-offcanvas">
        Launch demo offcanvas
    </button>

    <twig:Offcanvas id="demo-offcanvas">
        <twig:Offcanvas:Header>
            <twig:Offcanvas:Title>Offcanvas</twig:Offcanvas:Title>
        </twig:Offcanvas:Header>
        <twig:Offcanvas:Body>
            <p>Content for the offcanvas goes here. You can place just about any Bootstrap component or custom element here.</p>
            <div class="dropdown mt-3">
                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Dropdown button</button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">Action</a></li>
                    <li><a class="dropdown-item" href="#">Another action</a></li>
                </ul>
            </div>
        </twig:Offcanvas:Body>
    </twig:Offcanvas>
</div>
```

## Installation

::: installation

## Usage

```twig
<button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#navigation-panel" aria-controls="navigation-panel">
    Open panel
</button>

<twig:Offcanvas id="navigation-panel">
    <twig:Offcanvas:Header>
        <twig:Offcanvas:Title>Navigation</twig:Offcanvas:Title>
    </twig:Offcanvas:Header>
    <twig:Offcanvas:Body>
        Add navigation links or any other content here.
    </twig:Offcanvas:Body>
</twig:Offcanvas>
```

## Accessibility

Every trigger must identify the panel with either `href` or `data-bs-target` and expose the same id through `aria-controls`. The offcanvas root derives its `aria-labelledby` from its `id`, and `Offcanvas:Title` automatically applies the matching id, so the panel is always correctly labelled.

Bootstrap adds the dialog role and manages focus when a non-responsive offcanvas opens. Keep a visible dismiss control in the header, use descriptive trigger text, and verify that responsive panels remain understandable when they become regular page content above their breakpoint.

## Examples

### Offcanvas components

Display the complete panel structure statically while composing its content.

```twig {"preview":true}
<twig:Offcanvas id="static-offcanvas" shown class="position-static w-100">
    <twig:Offcanvas:Header>
        <twig:Offcanvas:Title>Offcanvas</twig:Offcanvas:Title>
    </twig:Offcanvas:Header>
    <twig:Offcanvas:Body>
        Content for the offcanvas goes here. You can place just about any Bootstrap component or custom element here.
    </twig:Offcanvas:Body>
</twig:Offcanvas>
```

### Live demo

Open the same panel from either a link or a button.

```twig {"preview":true}
<div class="d-flex flex-wrap justify-content-center align-items-start gap-2 w-100">
    <a class="btn btn-primary" data-bs-toggle="offcanvas" href="#live-offcanvas" role="button" aria-controls="live-offcanvas">Link with href</a>
    <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#live-offcanvas" aria-controls="live-offcanvas">Button with data-bs-target</button>

    <twig:Offcanvas id="live-offcanvas">
        <twig:Offcanvas:Header>
            <twig:Offcanvas:Title>Offcanvas</twig:Offcanvas:Title>
        </twig:Offcanvas:Header>
        <twig:Offcanvas:Body>
            <p>Content for the offcanvas goes here. You can place just about any Bootstrap component or custom element here.</p>
            <div class="dropdown mt-3">
                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Dropdown button</button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">Action</a></li>
                    <li><a class="dropdown-item" href="#">Another action</a></li>
                </ul>
            </div>
        </twig:Offcanvas:Body>
    </twig:Offcanvas>
</div>
```

### Body scrolling

Allow body scrolling without displaying a backdrop.

```twig {"preview":true}
<div class="d-flex justify-content-center w-100">
    <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#scrolling-offcanvas" aria-controls="scrolling-offcanvas">Enable body scrolling</button>

    <twig:Offcanvas id="scrolling-offcanvas" scroll :backdrop="false">
        <twig:Offcanvas:Header>
            <twig:Offcanvas:Title>Offcanvas with body scrolling</twig:Offcanvas:Title>
        </twig:Offcanvas:Header>
        <twig:Offcanvas:Body>
            <p>Try scrolling the rest of the page while this offcanvas is open.</p>
        </twig:Offcanvas:Body>
    </twig:Offcanvas>
</div>
```

### Body scrolling and backdrop

Keep the body scrollable while retaining the backdrop.

```twig {"preview":true}
<div class="d-flex justify-content-center w-100">
    <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#scrolling-backdrop-offcanvas" aria-controls="scrolling-backdrop-offcanvas">Enable both scrolling and backdrop</button>

    <twig:Offcanvas id="scrolling-backdrop-offcanvas" scroll>
        <twig:Offcanvas:Header>
            <twig:Offcanvas:Title>Backdrop with scrolling</twig:Offcanvas:Title>
        </twig:Offcanvas:Header>
        <twig:Offcanvas:Body>
            <p>Try scrolling the rest of the page while this offcanvas is open.</p>
        </twig:Offcanvas:Body>
    </twig:Offcanvas>
</div>
```

### Static backdrop

Require an explicit dismiss action instead of closing on backdrop clicks.

```twig {"preview":true}
<div class="d-flex justify-content-center w-100">
    <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#static-backdrop-offcanvas" aria-controls="static-backdrop-offcanvas">Toggle static offcanvas</button>

    <twig:Offcanvas id="static-backdrop-offcanvas" backdrop="static">
        <twig:Offcanvas:Header>
            <twig:Offcanvas:Title>Offcanvas</twig:Offcanvas:Title>
        </twig:Offcanvas:Header>
        <twig:Offcanvas:Body>
            <p>I will not close if you click outside of me.</p>
        </twig:Offcanvas:Body>
    </twig:Offcanvas>
</div>
```

### Dark offcanvas

Apply Bootstrap's dark color mode to the panel and its close button.

```twig {"preview":true}
<div class="d-flex justify-content-center w-100">
    <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#dark-offcanvas" aria-controls="dark-offcanvas">Toggle dark offcanvas</button>

    <twig:Offcanvas id="dark-offcanvas" theme="dark">
        <twig:Offcanvas:Header>
            <twig:Offcanvas:Title>Dark offcanvas</twig:Offcanvas:Title>
        </twig:Offcanvas:Header>
        <twig:Offcanvas:Body>
            <p>Place offcanvas content here.</p>
        </twig:Offcanvas:Body>
    </twig:Offcanvas>
</div>
```

### Responsive

Turn an offcanvas panel into regular content at a selected breakpoint.

```twig {"preview":true}
<div class="w-100">
    <button class="btn btn-primary d-block mx-auto d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#responsive-offcanvas" aria-controls="responsive-offcanvas">Toggle offcanvas</button>

    <twig:Offcanvas id="responsive-offcanvas" responsive="lg" placement="end">
        <twig:Offcanvas:Header>
            <twig:Offcanvas:Title>Responsive offcanvas</twig:Offcanvas:Title>
        </twig:Offcanvas:Header>
        <twig:Offcanvas:Body>
            <p>This is content within an <code>.offcanvas-lg</code>.</p>
        </twig:Offcanvas:Body>
    </twig:Offcanvas>
</div>
```

### Placement

Open panels from any viewport edge.

```twig {"preview":true}
<div class="d-flex flex-wrap justify-content-center align-items-start gap-2 w-100">
    {% for placement in ['start', 'end', 'top', 'bottom'] %}
        <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-{{ placement }}" aria-controls="offcanvas-{{ placement }}">Toggle {{ placement }}</button>
        <twig:Offcanvas id="offcanvas-{{ placement }}" placement="{{ placement }}">
            <twig:Offcanvas:Header>
                <twig:Offcanvas:Title>Offcanvas {{ placement }}</twig:Offcanvas:Title>
            </twig:Offcanvas:Header>
            <twig:Offcanvas:Body>
                <p>Offcanvas content from the {{ placement }} edge.</p>
            </twig:Offcanvas:Body>
        </twig:Offcanvas>
    {% endfor %}
</div>
```

## API Reference

::: api-reference
