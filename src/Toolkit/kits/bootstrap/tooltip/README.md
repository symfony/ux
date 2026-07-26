# Tooltip

Add Bootstrap tooltips to focusable controls with configurable content and placement.

```twig {"preview":true}
<div class="d-flex flex-wrap gap-2">
    <twig:Tooltip title="Tooltip on left" placement="left">
        <button type="button" class="btn btn-secondary" {{ html_attr(tooltip_trigger_attrs) }}>Left</button>
    </twig:Tooltip>
    <twig:Tooltip title="Tooltip on top" placement="top">
        <button type="button" class="btn btn-secondary" {{ html_attr(tooltip_trigger_attrs) }}>Top</button>
    </twig:Tooltip>
    <twig:Tooltip title="Tooltip on bottom" placement="bottom">
        <button type="button" class="btn btn-secondary" {{ html_attr(tooltip_trigger_attrs) }}>Bottom</button>
    </twig:Tooltip>
    <twig:Tooltip title="Tooltip on right" placement="right">
        <button type="button" class="btn btn-secondary" {{ html_attr(tooltip_trigger_attrs) }}>Right</button>
    </twig:Tooltip>
</div>

<script type="module">
    import { Tooltip } from 'bootstrap';

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => Tooltip.getOrCreateInstance(element));
</script>
```

## Installation

::: installation

## Usage

```twig
<twig:Tooltip title="Save your changes">
    <button type="button" class="btn btn-primary" {{ html_attr(tooltip_trigger_attrs) }}>Save</button>
</twig:Tooltip>

<script type="module">
    import { Tooltip } from 'bootstrap';

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => Tooltip.getOrCreateInstance(element));
</script>
```

## Accessibility

Apply `tooltip_trigger_attrs` to a natively focusable, interactive element such as a button or link so the tooltip works with both pointer and keyboard input. Do not configure hover as the only trigger.

Disabled controls cannot receive focus or pointer events. Put the tooltip attributes on a focusable wrapper as shown below. Bootstrap sanitizes HTML tooltip content by default, but plain text remains preferable for user-provided content.

## Examples

### Enable tooltips

Bootstrap tooltips are opt-in and must be initialized explicitly after Bootstrap's JavaScript is loaded.

```twig {"preview":true}
<twig:Tooltip title="This tooltip is initialized explicitly">
    <button type="button" class="btn btn-secondary" {{ html_attr(tooltip_trigger_attrs) }}>Hover or focus me</button>
</twig:Tooltip>

<script type="module">
    import { Tooltip } from 'bootstrap';

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => Tooltip.getOrCreateInstance(element));
</script>
```

### Tooltips on links

Apply the trigger attributes directly to inline links so they remain keyboard accessible.

```twig {"preview":true}
<p class="text-body-secondary mb-0">
    Placeholder text to demonstrate
    <twig:Tooltip title="Default tooltip"><a href="#" {{ html_attr(tooltip_trigger_attrs) }}>inline links</a></twig:Tooltip>
    with tooltips. Content placed here mimics how
    <twig:Tooltip title="Another tooltip"><a href="#" {{ html_attr(tooltip_trigger_attrs) }}>real text</a></twig:Tooltip>
    flows around them. You can use
    <twig:Tooltip title="Another one here too"><a href="#" {{ html_attr(tooltip_trigger_attrs) }}>these tooltips on links</a></twig:Tooltip>
    in your own interface.
</p>

<script type="module">
    import { Tooltip } from 'bootstrap';

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => Tooltip.getOrCreateInstance(element));
</script>
```

### Custom tooltips

Use a custom class and Bootstrap CSS variables to change a generated tooltip's appearance.

```twig {"preview":true}
<style>
    .bootstrap-custom-tooltip {
        --bs-tooltip-bg: var(--bs-primary);
        --bs-tooltip-color: var(--bs-white);
    }
</style>

<twig:Tooltip title="This top tooltip is themed via CSS variables." customClass="bootstrap-custom-tooltip">
    <button type="button" class="btn btn-secondary" {{ html_attr(tooltip_trigger_attrs) }}>Custom tooltip</button>
</twig:Tooltip>

<script type="module">
    import { Tooltip } from 'bootstrap';

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => Tooltip.getOrCreateInstance(element));
</script>
```

### Directions

Choose a preferred placement or enable Bootstrap's sanitized HTML content.

```twig {"preview":true}
<div class="d-flex flex-wrap gap-2">
    <twig:Tooltip title="Tooltip on top" placement="top">
        <button type="button" class="btn btn-secondary" {{ html_attr(tooltip_trigger_attrs) }}>Tooltip on top</button>
    </twig:Tooltip>
    <twig:Tooltip title="Tooltip on right" placement="right">
        <button type="button" class="btn btn-secondary" {{ html_attr(tooltip_trigger_attrs) }}>Tooltip on right</button>
    </twig:Tooltip>
    <twig:Tooltip title="Tooltip on bottom" placement="bottom">
        <button type="button" class="btn btn-secondary" {{ html_attr(tooltip_trigger_attrs) }}>Tooltip on bottom</button>
    </twig:Tooltip>
    <twig:Tooltip title="Tooltip on left" placement="left">
        <button type="button" class="btn btn-secondary" {{ html_attr(tooltip_trigger_attrs) }}>Tooltip on left</button>
    </twig:Tooltip>
    <twig:Tooltip title="<em>Tooltip</em> <u>with</u> <b>HTML</b>" html>
        <button type="button" class="btn btn-secondary" {{ html_attr(tooltip_trigger_attrs) }}>Tooltip with HTML</button>
    </twig:Tooltip>
</div>

<script type="module">
    import { Tooltip } from 'bootstrap';

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => Tooltip.getOrCreateInstance(element));
</script>
```

### Disabled elements

Put the trigger on a focusable wrapper when the described control is disabled.

```twig {"preview":true}
<twig:Tooltip title="Disabled tooltip">
    <span class="d-inline-block" tabindex="0" {{ html_attr(tooltip_trigger_attrs) }}>
        <button class="btn btn-primary" type="button" disabled>Disabled button</button>
    </span>
</twig:Tooltip>

<script type="module">
    import { Tooltip } from 'bootstrap';

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => Tooltip.getOrCreateInstance(element));
</script>
```

## API Reference

::: api-reference
