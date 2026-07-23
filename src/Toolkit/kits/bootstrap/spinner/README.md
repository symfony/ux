# Spinner

Indicate the loading state of a component or page with Bootstrap spinners.

```twig {"preview":true,"height":"130px"}
<div class="d-flex align-items-center gap-3">
    <twig:Spinner color="primary" label="Loading..." />
    <twig:Spinner type="grow" color="secondary" label="Loading..." />
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Spinner label="Loading search results..." />
```

## Accessibility

Standalone spinners use `role="status"` and include a visually hidden loading message. Customize `label` so the announced status describes what is loading.

When visible text already announces the status, set `decorative` to hide the spinner itself from assistive technologies.

## Examples

### Border spinner

Use the border animation for a lightweight loading indicator.

```twig {"preview":true,"height":"130px"}
<twig:Spinner label="Loading..." />
```

### Colors

The spinner inherits `currentColor`; use Bootstrap contextual text colors to customize it.

```twig {"preview":true,"height":"130px"}
<div class="d-flex flex-wrap gap-3">
    <twig:Spinner color="primary" label="Loading..." />
    <twig:Spinner color="secondary" label="Loading..." />
    <twig:Spinner color="success" label="Loading..." />
    <twig:Spinner color="danger" label="Loading..." />
    <twig:Spinner color="warning" label="Loading..." />
    <twig:Spinner color="info" label="Loading..." />
    <twig:Spinner color="light" label="Loading..." />
    <twig:Spinner color="dark" label="Loading..." />
</div>
```

### Growing spinner

Switch to the growing animation while keeping the same contextual color options.

```twig {"preview":true,"height":"130px"}
<div class="d-flex flex-wrap gap-3">
    <twig:Spinner type="grow" label="Loading..." />
    <twig:Spinner type="grow" color="primary" label="Loading..." />
    <twig:Spinner type="grow" color="secondary" label="Loading..." />
    <twig:Spinner type="grow" color="success" label="Loading..." />
    <twig:Spinner type="grow" color="danger" label="Loading..." />
    <twig:Spinner type="grow" color="warning" label="Loading..." />
    <twig:Spinner type="grow" color="info" label="Loading..." />
    <twig:Spinner type="grow" color="light" label="Loading..." />
    <twig:Spinner type="grow" color="dark" label="Loading..." />
</div>
```

### Margin

Use Bootstrap spacing utilities to add space around a spinner.

```twig {"preview":true,"height":"230px"}
<twig:Spinner class="m-5" label="Loading..." />
```

### Flex placement

Use flexbox utilities to center a spinner or place it after a visible loading status.

```twig {"preview":true,"height":"190px"}
<div class="d-flex flex-column gap-4">
    <div class="d-flex justify-content-center">
        <twig:Spinner label="Loading..." />
    </div>

    <div class="d-flex align-items-center">
        <strong role="status">Loading...</strong>
        <twig:Spinner class="ms-auto" decorative />
    </div>
</div>
```

### Floats

Use float utilities when a spinner needs to follow the edge of its container.

```twig {"preview":true,"height":"130px"}
<div class="clearfix">
    <twig:Spinner class="float-end" label="Loading..." />
</div>
```

### Text align

Because the component is inline-flex, text alignment utilities can position it within a block.

```twig {"preview":true,"height":"130px"}
<div class="text-center">
    <twig:Spinner label="Loading..." />
</div>
```

### Size

Use the compact Bootstrap size or set custom dimensions with CSS.

```twig {"preview":true,"height":"150px"}
<div class="d-flex align-items-center gap-3">
    <twig:Spinner small label="Loading..." />
    <twig:Spinner type="grow" small label="Loading..." />
    <twig:Spinner style="width: 3rem; height: 3rem" label="Loading..." />
    <twig:Spinner type="grow" style="width: 3rem; height: 3rem" label="Loading..." />
</div>
```

### Buttons

Place a decorative spinner inside a disabled button and provide the loading status as button text or visually hidden text.

```twig {"preview":true,"height":"140px"}
<div class="d-flex flex-wrap gap-2">
    <button class="btn btn-primary" type="button" disabled>
        <twig:Spinner small decorative />
        <span class="visually-hidden">Loading...</span>
    </button>
    <button class="btn btn-primary" type="button" disabled>
        <twig:Spinner small decorative />
        <span>Loading...</span>
    </button>
    <button class="btn btn-primary" type="button" disabled>
        <twig:Spinner type="grow" small decorative />
        <span class="visually-hidden">Loading...</span>
    </button>
    <button class="btn btn-primary" type="button" disabled>
        <twig:Spinner type="grow" small decorative />
        <span>Loading...</span>
    </button>
</div>
```

## API Reference

::: api-reference
