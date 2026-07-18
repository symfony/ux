# Collapse

Toggle the visibility of content with Bootstrap's Collapse plugin.

```twig {"preview":true,"height":"220px"}
<div>
    <button
        class="btn btn-primary"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#collapse-demo"
        aria-expanded="false"
        aria-controls="collapse-demo"
    >
        Show project details
    </button>
    <twig:Collapse id="collapse-demo" class="mt-3">
        <div class="card card-body">
            This panel is controlled by Bootstrap's Collapse plugin. Its trigger automatically keeps <code>aria-expanded</code> synchronized.
        </div>
    </twig:Collapse>
</div>
```

## Installation

::: installation

## Usage

```twig
<button
    class="btn btn-primary"
    type="button"
    data-bs-toggle="collapse"
    data-bs-target="#details-collapse"
    aria-expanded="false"
    aria-controls="details-collapse"
>
    Toggle details
</button>

<twig:Collapse id="details-collapse" class="mt-3">
    Additional details appear here.
</twig:Collapse>
```

## Accessibility

Prefer a native button trigger. If an anchor is used, add `role="button"`. Set the initial `aria-expanded` value to match the `show` prop and point `aria-controls` to the collapsible element's ID.

Bootstrap synchronizes `aria-expanded` while toggling. Avoid padding directly on the collapse element because Bootstrap animates its height or width; place padding on an inner element instead.

## Examples

Control the same collapsible panel with a link or a button.

```twig {"preview":true,"height":"240px"}
<p class="d-flex gap-2">
    <a
        class="btn btn-primary"
        data-bs-toggle="collapse"
        href="#collapse-example"
        role="button"
        aria-expanded="false"
        aria-controls="collapse-example"
    >
        Link with href
    </a>
    <button
        class="btn btn-primary"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#collapse-example"
        aria-expanded="false"
        aria-controls="collapse-example"
    >
        Button with data-bs-target
    </button>
</p>
<twig:Collapse id="collapse-example">
    <div class="card card-body">
        Some placeholder content for the collapse component. This panel can be toggled by either control.
    </div>
</twig:Collapse>
```

### Horizontal

Animate width instead of height and set a width on the immediate child.

```twig {"preview":true,"height":"240px"}
<div>
    <button
        class="btn btn-primary"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#collapse-width-example"
        aria-expanded="false"
        aria-controls="collapse-width-example"
    >
        Toggle width collapse
    </button>
</div>
<div class="mt-3" style="min-height: 120px;">
    <twig:Collapse id="collapse-width-example" horizontal>
        <div class="card card-body" style="width: 300px;">
            This content collapses horizontally. The immediate child defines the expanded width.
        </div>
    </twig:Collapse>
</div>
```

### Multiple toggles and targets

Use ID and class selectors to control individual panels or several targets together.

```twig {"preview":true,"height":"280px"}
<p class="d-flex flex-wrap gap-2">
    <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#multi-collapse-one" aria-expanded="false" aria-controls="multi-collapse-one">Toggle first element</button>
    <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#multi-collapse-two" aria-expanded="false" aria-controls="multi-collapse-two">Toggle second element</button>
    <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target=".multi-collapse" aria-expanded="false" aria-controls="multi-collapse-one multi-collapse-two">Toggle both elements</button>
</p>
<div class="row">
    <div class="col">
        <twig:Collapse id="multi-collapse-one" class="multi-collapse">
            <div class="card card-body">The first panel can be toggled independently or together with the second.</div>
        </twig:Collapse>
    </div>
    <div class="col">
        <twig:Collapse id="multi-collapse-two" class="multi-collapse">
            <div class="card card-body">The second panel responds to its ID and the shared class selector.</div>
        </twig:Collapse>
    </div>
</div>
```

## API Reference

::: api-reference
