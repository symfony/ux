# Placeholder

Uses loading placeholders to indicate that content may still be loading.

```twig {"preview":true,"height":"380px"}
<div class="card" style="width: 18rem;" aria-busy="true">
    <svg class="card-img-top text-secondary bg-body-secondary" width="100%" height="140" role="img" aria-label="Loading image placeholder">
        <rect width="100%" height="100%" fill="currentColor" />
    </svg>
    <div class="card-body">
        <twig:Placeholder:Animation animation="glow" tag="h5" class="card-title">
            <twig:Placeholder class="col-6" />
        </twig:Placeholder:Animation>
        <twig:Placeholder:Animation animation="glow" tag="p" class="card-text">
            <twig:Placeholder class="col-7" />
            <twig:Placeholder class="col-4" />
            <twig:Placeholder class="col-4" />
            <twig:Placeholder class="col-6" />
            <twig:Placeholder class="col-8" />
        </twig:Placeholder:Animation>
        <twig:Placeholder tag="a" class="btn btn-primary disabled col-6" aria-disabled="true" />
    </div>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Placeholder class="col-6" />
```

## Accessibility

Placeholders are decorative and hidden from assistive technologies. Mark the loading region with `aria-busy="true"`, then announce meaningful content changes when loading completes.

The application remains responsible for replacing placeholders and informing users when the updated content is available.

## Examples

Match a placeholder card's proportions to the content it temporarily replaces.

```twig {"preview":true,"height":"700px"}
<div class="row g-3">
    <div class="col-md-6">
        <div class="card h-100">
            <svg class="card-img-top text-secondary bg-body-secondary" width="100%" height="140" role="img" aria-label="Example image placeholder">
                <rect width="100%" height="100%" fill="currentColor" />
            </svg>
            <div class="card-body">
                <h5 class="card-title">Card title</h5>
                <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
                <a href="#" class="btn btn-primary">Go somewhere</a>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100" aria-hidden="true">
            <svg class="card-img-top text-secondary bg-body-secondary" width="100%" height="140">
                <rect width="100%" height="100%" fill="currentColor" />
            </svg>
            <div class="card-body">
                <twig:Placeholder:Animation animation="glow" tag="h5" class="card-title">
                    <twig:Placeholder class="col-6" />
                </twig:Placeholder:Animation>
                <twig:Placeholder:Animation animation="glow" tag="p" class="card-text">
                    <twig:Placeholder class="col-7" />
                    <twig:Placeholder class="col-4" />
                    <twig:Placeholder class="col-4" />
                    <twig:Placeholder class="col-6" />
                    <twig:Placeholder class="col-8" />
                </twig:Placeholder:Animation>
                <twig:Placeholder tag="a" class="btn btn-primary disabled col-6" aria-disabled="true" />
            </div>
        </div>
    </div>
</div>
```

### How it works

Use placeholders as text replacements or modifiers on existing components.

```twig {"preview":true,"height":"120px"}
<p aria-hidden="true">
    <twig:Placeholder class="col-6" />
</p>

<twig:Placeholder tag="a" class="btn btn-primary disabled col-4" aria-disabled="true" />
```

### Width

Set widths with grid columns, width utilities, or inline styles.

```twig {"preview":true,"height":"140px"}
<div class="w-100 d-flex flex-column gap-2">
    <twig:Placeholder class="col-6" />
    <twig:Placeholder class="w-75" />
    <twig:Placeholder style="width: 25%;" />
</div>
```

### Color

Use the inherited text color or Bootstrap background utilities.

```twig {"preview":true,"height":"270px"}
<div class="w-100 d-flex flex-column gap-2">
    <twig:Placeholder class="col-12" />
    <twig:Placeholder class="col-12 bg-primary" />
    <twig:Placeholder class="col-12 bg-secondary" />
    <twig:Placeholder class="col-12 bg-success" />
    <twig:Placeholder class="col-12 bg-danger" />
    <twig:Placeholder class="col-12 bg-warning" />
    <twig:Placeholder class="col-12 bg-info" />
    <twig:Placeholder class="col-12 bg-light" />
    <twig:Placeholder class="col-12 bg-dark" />
</div>
```

### Sizing

Adjust placeholder height with large, small, and extra-small variants.

```twig {"preview":true,"height":"150px"}
<div class="w-100 d-flex flex-column gap-2">
    <twig:Placeholder size="lg" class="col-12" />
    <twig:Placeholder class="col-12" />
    <twig:Placeholder size="sm" class="col-12" />
    <twig:Placeholder size="xs" class="col-12" />
</div>
```

### Animation

Use glow or wave animation to reinforce that content is actively loading.

```twig {"preview":true,"height":"120px"}
<div class="w-100 d-flex flex-column gap-3">
    <twig:Placeholder:Animation animation="glow" tag="p" class="mb-0">
        <twig:Placeholder class="col-12" />
    </twig:Placeholder:Animation>

    <twig:Placeholder:Animation animation="wave" tag="p" class="mb-0">
        <twig:Placeholder class="col-12" />
    </twig:Placeholder:Animation>
</div>
```

## API Reference

::: api-reference
