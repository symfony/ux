# Progress

Display task progress with accessible labels, stacked bars, contextual colors, and animation.

```twig {"preview":true,"height":"180px"}
<div class="vstack gap-3" style="width: min(100%, 32rem);">
    <twig:Progress value="25" ariaLabel="Project setup" label="25%" />
    <twig:Progress value="50" ariaLabel="Content migration" color="success" striped label="50%" />
    <twig:Progress value="75" ariaLabel="Deployment" color="info" striped animated label="75%" />
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Progress value="25" ariaLabel="File upload progress" label="25%" />
```

## Accessibility

Give every progress indicator a concise accessible name with `ariaLabel`. Keep `value`, `min`, and `max` synchronized with the task's real state.

Do not rely on color alone to communicate status. Long labels can cross both the filled and unfilled backgrounds, so prefer a separate visible label when contrast cannot be guaranteed.

## Examples

### How it works

Set the current value to update both the visual width and the progressbar accessibility attributes.

```twig {"preview":true,"height":"210px"}
<div class="vstack gap-2">
    {% for value in [0, 25, 50, 75, 100] %}
        <twig:Progress :value="value" ariaLabel="Basic example" />
    {% endfor %}
</div>
```

### Width

Use Bootstrap width utilities when the visual width should be controlled by a class.

```twig {"preview":true,"height":"120px"}
<div class="w-100">
    <twig:Progress value="75" ariaLabel="Width utility example" barClass="w-75" :barWidth="false" />
</div>
```

### Height

Set a custom height on the progress container.

```twig {"preview":true,"height":"140px"}
<div class="vstack gap-3">
    <twig:Progress value="25" ariaLabel="Example 1px high" height="1px" />
    <twig:Progress value="25" ariaLabel="Example 20px high" height="20px" />
</div>
```

### Labels

Display short text inside the bar, with care for overflow and contrast when labels are long.

```twig {"preview":true,"height":"150px"}
<div class="vstack gap-3">
    <twig:Progress value="25" ariaLabel="Example with label" label="25%" />
    <twig:Progress
        value="10"
        ariaLabel="Example with long label"
        label="Long label text for the progress bar"
        barClass="overflow-visible text-dark"
    />
</div>
```

### Backgrounds

Apply contextual background colors, with matching text-background helpers for labeled bars.

```twig {"preview":true,"height":"340px"}
<div class="vstack gap-3">
    <twig:Progress value="25" ariaLabel="Success example" color="success" />
    <twig:Progress value="50" ariaLabel="Info example" color="info" />
    <twig:Progress value="75" ariaLabel="Warning example" color="warning" />
    <twig:Progress value="100" ariaLabel="Danger example" color="danger" />

    <twig:Progress value="25" ariaLabel="Labeled success example" barClass="text-bg-success" label="25%" />
    <twig:Progress value="50" ariaLabel="Labeled info example" barClass="text-bg-info" label="50%" />
    <twig:Progress value="75" ariaLabel="Labeled warning example" barClass="text-bg-warning" label="75%" />
    <twig:Progress value="100" ariaLabel="Labeled danger example" barClass="text-bg-danger" label="100%" />
</div>
```

### Multiple bars

Wrap stacked progress segments in Bootstrap's `progress-stacked` container.

```twig {"preview":true,"height":"120px"}
<div class="progress-stacked">
    <twig:Progress value="15" ariaLabel="Segment one" stacked />
    <twig:Progress value="30" ariaLabel="Segment two" color="success" stacked />
    <twig:Progress value="20" ariaLabel="Segment three" color="info" stacked />
</div>
```

### Striped

Add striped styling to default and contextual progress bars.

```twig {"preview":true,"height":"250px"}
<div class="vstack gap-3">
    <twig:Progress value="10" ariaLabel="Default striped example" striped />
    <twig:Progress value="25" ariaLabel="Success striped example" color="success" striped />
    <twig:Progress value="50" ariaLabel="Info striped example" color="info" striped />
    <twig:Progress value="75" ariaLabel="Warning striped example" color="warning" striped />
    <twig:Progress value="100" ariaLabel="Danger striped example" color="danger" striped />
</div>
```

### Animated stripes

Animate a striped bar to emphasize an actively changing task.

```twig {"preview":true,"height":"120px"}
<div class="w-100">
    <twig:Progress value="75" ariaLabel="Animated striped example" striped animated />
</div>
```

## API Reference

::: api-reference
