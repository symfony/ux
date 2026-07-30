# Tooltip

A Stimulus behavior that shows a tooltip from a `content` value — or straight from an element's native `title`. Powered by [Floating UI](https://floating-ui.com/): the tooltip is rendered outside the trigger (appended to `<body>`), so it's never clipped by an `overflow` ancestor, and it flips and shifts to stay in view. Put it right on the trigger — it opens on hover and keyboard focus by default — and style the injected `.tooltip` once, globally.

```twig {"preview":true,"collapseClass":true}
<style>
    .tooltip { position: absolute; top: 0; left: 0; width: max-content; border-radius: .25rem; background: #4c1d95; color: #fff; padding: .25rem .5rem; font-size: .75rem; font-weight: 500; pointer-events: none; z-index: 50; }
    .tooltip-arrow { position: absolute; width: 8px; height: 8px; background: inherit; transform: rotate(45deg); }
</style>
<div class="flex min-h-[140px] items-center justify-center">
    <button type="button" data-controller="tooltip" title="Thanks for hovering!" class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-500">
        Hover me
    </button>
</div>
```

## Installation

::: installation

## Usage

Style every tooltip once, in your global stylesheet — Floating UI sets the position and the arrow's offsets, so you only own the look:

```css
.tooltip {
    position: absolute;
    top: 0;
    left: 0;
    width: max-content;
    border-radius: 0.25rem;
    background: #1f2937;
    color: #fff;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    pointer-events: none;
    z-index: 50;
}
.tooltip-arrow {
    position: absolute;
    width: 8px;
    height: 8px;
    background: inherit;
    transform: rotate(45deg);
}
```

Then put the controller on the trigger with a `content` value — hover and focus are wired for you:

```twig
<button type="button" data-controller="tooltip" data-tooltip-content-value="Tooltip content">
    Hover me
</button>
```

Set `placement` to hint a side (`top` by default, `bottom`, `left`, or `right`) — Floating UI flips and shifts from there to keep it on screen. Set `trigger` to `click` to toggle on click, or `manual` to drive `show`/`hide` yourself (for example from another controller's event). The controller injects the `.tooltip` into `<body>` and points the trigger's `aria-describedby` at it.

As a shortcut — the form shown at the top — drop the controller onto an element that already has a `title` and skip the `content` value: the controller adopts the title text and removes the attribute so the browser's native tooltip is suppressed.

## Examples

### Placement

Set `placement` to `top` (default), `bottom`, `left`, or `right`. It's a _preference_ — Floating UI flips to the opposite side and shifts along the axis whenever the trigger is too close to an edge:

```twig {"preview":true,"collapseClass":true}
<style>
    .tooltip { position: absolute; top: 0; left: 0; width: max-content; border-radius: .25rem; background: #4c1d95; color: #fff; padding: .25rem .5rem; font-size: .75rem; font-weight: 500; pointer-events: none; z-index: 50; }
    .tooltip-arrow { position: absolute; width: 8px; height: 8px; background: inherit; transform: rotate(45deg); }
</style>
<div class="flex min-h-[200px] items-center justify-center gap-12 px-16">
    <button type="button" data-controller="tooltip" data-tooltip-content-value="Top" data-tooltip-placement-value="top" class="rounded-md bg-violet-600 px-3 py-2 text-sm font-medium text-white hover:bg-violet-500">Top</button>
    <button type="button" data-controller="tooltip" data-tooltip-content-value="Bottom" data-tooltip-placement-value="bottom" class="rounded-md bg-violet-600 px-3 py-2 text-sm font-medium text-white hover:bg-violet-500">Bottom</button>
    <button type="button" data-controller="tooltip" data-tooltip-content-value="Left" data-tooltip-placement-value="left" class="rounded-md bg-violet-600 px-3 py-2 text-sm font-medium text-white hover:bg-violet-500">Left</button>
    <button type="button" data-controller="tooltip" data-tooltip-content-value="Right" data-tooltip-placement-value="right" class="rounded-md bg-violet-600 px-3 py-2 text-sm font-medium text-white hover:bg-violet-500">Right</button>
</div>
```

### Click to Toggle

Set `trigger` to `click` for a tooltip that stays open until the next click:

```twig {"preview":true,"collapseClass":true}
<style>
    .tooltip { position: absolute; top: 0; left: 0; width: max-content; border-radius: .25rem; background: #4c1d95; color: #fff; padding: .25rem .5rem; font-size: .75rem; font-weight: 500; pointer-events: none; z-index: 50; }
    .tooltip-arrow { position: absolute; width: 8px; height: 8px; background: inherit; transform: rotate(45deg); }
</style>
<div class="flex min-h-[120px] items-center justify-center">
    <button type="button" data-controller="tooltip" data-tooltip-content-value="Click again to close" data-tooltip-placement-value="bottom" data-tooltip-trigger-value="click" class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-500">
        Click me
    </button>
</div>
```

### Auto-hide

Set `autoHide` (milliseconds) so a shown tooltip dismisses itself — handy when it's opened programmatically rather than by a hover you can leave:

```twig {"preview":true,"collapseClass":true}
<style>
    .tooltip { position: absolute; top: 0; left: 0; width: max-content; border-radius: .25rem; background: #4c1d95; color: #fff; padding: .25rem .5rem; font-size: .75rem; font-weight: 500; pointer-events: none; z-index: 50; }
    .tooltip-arrow { position: absolute; width: 8px; height: 8px; background: inherit; transform: rotate(45deg); }
</style>
<div class="flex min-h-[120px] items-center justify-center">
    <button type="button" data-controller="tooltip" data-tooltip-content-value="I'll disappear shortly…" data-tooltip-placement-value="bottom" data-tooltip-trigger-value="click" data-tooltip-auto-hide-value="2000" class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-500">
        Show for 2s
    </button>
</div>
```

### Manual Control

With `trigger` set to `manual`, nothing is wired automatically — you drive `show`, `hide`, and `toggle` yourself, from your own actions or another controller's event. The tooltip anchors to the controller's element:

```twig {"preview":true,"collapseClass":true}
<style>
    .tooltip { position: absolute; top: 0; left: 0; width: max-content; border-radius: .25rem; background: #4c1d95; color: #fff; padding: .25rem .5rem; font-size: .75rem; font-weight: 500; pointer-events: none; z-index: 50; }
    .tooltip-arrow { position: absolute; width: 8px; height: 8px; background: inherit; transform: rotate(45deg); }
</style>
<div class="flex min-h-[120px] items-center justify-center">
    <div data-controller="tooltip" data-tooltip-content-value="Toggled by hand" data-tooltip-trigger-value="manual" class="inline-flex gap-2">
        <button type="button" data-action="tooltip#show" class="rounded-md bg-violet-600 px-3 py-2 text-sm font-medium text-white hover:bg-violet-500">Show</button>
        <button type="button" data-action="tooltip#hide" class="rounded-md bg-violet-100 px-3 py-2 text-sm font-medium text-violet-900 hover:bg-violet-200 dark:bg-violet-900 dark:text-violet-100 dark:hover:bg-violet-800">Hide</button>
    </div>
</div>
```

## API Reference

::: api-reference
