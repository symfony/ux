# Clipboard

A Stimulus behavior that copies text to the clipboard — either a value you give it or the content of an element — with optional copied feedback.

```twig {"preview":true,"collapseClass":true}
<div data-controller="clipboard" class="flex items-center gap-3">
    <code data-clipboard-target="source" class="rounded-md bg-violet-50 px-3 py-2 font-mono text-sm text-violet-900 dark:bg-violet-950 dark:text-violet-100">composer require symfony/ux-toolkit</code>
    <span class="relative">
        <button type="button" data-action="clipboard#copy" class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-violet-500 active:scale-95 active:bg-violet-700">Copy</button>
        <span data-clipboard-target="success" class="absolute left-full top-1/2 ml-3 -translate-y-1/2 whitespace-nowrap text-sm font-medium text-violet-600 dark:text-violet-400">Copied!</span>
    </span>
</div>
```

## Installation

::: installation

## Usage

Add `data-controller="clipboard"`, mark the element to copy from with a `source` target, then trigger `clipboard#copy` from a button:

```twig
<div data-controller="clipboard">
    <code data-clipboard-target="source">Text to copy</code>
    <button type="button" data-action="clipboard#copy">Copy</button>
</div>
```

To copy a fixed string instead of an element, set a `source` value (`data-clipboard-source-value`) — see [Copy using a Value](#content-copy-using-a-value).

> [!NOTE]
> Every successful copy also dispatches a `clipboard:copied` event, with the copied text available as `event.detail.text`. Listen for it to drive your own feedback.

## Examples

### Copy a Form Element's Value

Form controls — inputs, textareas, selects — are copied from their `value`:

```twig {"preview":true,"collapseClass":true}
<div data-controller="clipboard" class="flex items-center gap-3">
    <input type="text" data-clipboard-target="source" value="hello@example.com" readonly class="rounded-md border border-violet-200 bg-violet-50 px-3 py-2 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">
    <button type="button" data-action="clipboard#copy" class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-violet-500 active:scale-95 active:bg-violet-700">Copy email</button>
</div>
```

### Copy an Element's Text

For non-form elements, the `source` target's text content is copied:

```twig {"preview":true,"collapseClass":true}
<div data-controller="clipboard" class="flex items-center gap-3">
    <p data-clipboard-target="source" class="flex-1 text-sm text-violet-900 dark:text-violet-100">The quick brown fox jumps over the lazy dog.</p>
    <button type="button" data-action="clipboard#copy" class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-violet-500 active:scale-95 active:bg-violet-700">Copy text</button>
</div>
```

### Copy Code

A classic: a code block with its own copy button. Point the `source` target at the `<pre>` — its text content is copied verbatim, indentation and newlines included:

```twig {"preview":true,"collapseClass":true}
<div data-controller="clipboard" class="relative w-full max-w-sm">
    <pre data-clipboard-target="source" class="overflow-x-auto rounded-md bg-violet-950 p-4 font-mono text-xs leading-relaxed text-violet-100"><code>$response = new JsonResponse([
    'status' => 'ok',
]);</code></pre>
    <button type="button" data-action="clipboard#copy" class="absolute right-2 top-2 rounded bg-violet-700 px-2 py-1 text-xs font-medium text-violet-100 hover:bg-violet-600">
        <span data-clipboard-target="idle">Copy</span>
        <span data-clipboard-target="success" hidden>Copied!</span>
    </button>
</div>
```

### Copy using a Value

Set a `source` value to copy a fixed string, independent of what's shown on screen. Here the field displays a masked key while the button copies the real one:

```twig {"preview":true,"collapseClass":true}
<div data-controller="clipboard" data-clipboard-source-value="a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6" class="flex items-center gap-3">
    <input type="text" value="xxxxxxxxxxxxxxxxxxxx" readonly class="rounded-md border border-violet-200 bg-violet-50 px-3 py-2 font-mono text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">
    <button type="button" data-action="clipboard#copy" class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-violet-500 active:scale-95 active:bg-violet-700">Copy API Key</button>
</div>
```

### Copied Feedback

Add a `success` target to reveal a confirmation after a successful copy. It's hidden again after `successDuration` milliseconds (defaults to `2000`):

```twig {"preview":true,"collapseClass":true}
<div data-controller="clipboard" data-clipboard-success-duration-value="1500" class="flex items-center gap-3">
    <code data-clipboard-target="source" class="rounded-md bg-violet-50 px-3 py-2 font-mono text-sm text-violet-900 dark:bg-violet-950 dark:text-violet-100">symfony serve -d</code>
    <span class="relative">
        <button type="button" data-action="clipboard#copy" class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-violet-500 active:scale-95 active:bg-violet-700">Copy</button>
        <span data-clipboard-target="success" class="absolute left-full top-1/2 ml-3 -translate-y-1/2 whitespace-nowrap text-sm font-medium text-violet-600 dark:text-violet-400">Copied to clipboard!</span>
    </span>
</div>
```

### Swap the Button Label

Put both an `idle` and a `success` label inside the button: the `idle` one is hidden while the `success` one shows, swapping "Copy" for "Copied!" on click.

```twig {"preview":true,"collapseClass":true}
<div data-controller="clipboard" class="flex items-center gap-3">
    <code data-clipboard-target="source" class="rounded-md bg-violet-50 px-3 py-2 font-mono text-sm text-violet-900 dark:bg-violet-950 dark:text-violet-100">git clone git@github.com:symfony/ux.git</code>
    <button type="button" data-action="clipboard#copy" class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-violet-500 active:scale-95 active:bg-violet-700">
        <span data-clipboard-target="idle">Copy</span>
        <span data-clipboard-target="success" hidden>Copied!</span>
    </button>
</div>
```

### Animate the Feedback

The `success` target is toggled from `display: none` to visible on copy, and showing a hidden element restarts its CSS animations — so an animated `success` target replays its effect on every click. Give it a burst `@keyframes` and match `successDuration` to the animation's length:

```twig {"preview":true,"collapseClass":true}
<style>
@keyframes clipboard-burst {
    from { transform: scale(.75); opacity: .75; }
    to { transform: scale(2) rotate(20deg); opacity: 0; }
}
</style>
<div data-controller="clipboard" data-clipboard-success-duration-value="450" class="flex items-center gap-3">
    <code data-clipboard-target="source" class="rounded-md bg-violet-50 px-3 py-2 font-mono text-sm text-violet-900 dark:bg-violet-950 dark:text-violet-100">php bin/console cache:clear</code>
    <button type="button" data-action="clipboard#copy" class="relative rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-violet-500 active:scale-95 active:bg-violet-700">
        Copy
        <span data-clipboard-target="success" class="pointer-events-none absolute inset-0 rounded-full border-2 border-dotted border-violet-400" style="animation: clipboard-burst 450ms linear forwards"></span>
    </button>
</div>
```

### Feedback via a CSS Class

Prefer to drive feedback purely from CSS? Instead of `success`/`idle` targets, set a `success` class with `data-clipboard-success-class`. The controller adds it to its element for `successDuration`, so you can restyle anything beneath it — including the trigger itself:

> [!TIP]
> Pass multiple space-separated classes if you like (`data-clipboard-success-class="ring pulse"`) — they're all applied for the duration.

```twig {"preview":true,"collapseClass":true}
<style>.clipboard-copied .clipboard-btn { background-color: #16a34a; }</style>
<div data-controller="clipboard" data-clipboard-success-class="clipboard-copied" class="flex items-center gap-3">
    <code data-clipboard-target="source" class="rounded-md bg-violet-50 px-3 py-2 font-mono text-sm text-violet-900 dark:bg-violet-950 dark:text-violet-100">php bin/console about</code>
    <button type="button" data-action="clipboard#copy" class="clipboard-btn rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-violet-500 active:scale-95 active:bg-violet-700">Copy</button>
</div>
```

### Feedback with a Tooltip

Pair with the [`tooltip`](../tooltip) recipe for a floating confirmation. With both controllers on the button, copying dispatches `clipboard:copied` right there, opening a manual, self-hiding tooltip above it — no `success` target or class needed:

```twig {"preview":true,"collapseClass":true}
<style>
    .tooltip { position: absolute; top: 0; left: 0; width: max-content; border-radius: .25rem; background: #4c1d95; color: #fff; padding: .25rem .5rem; font-size: .75rem; font-weight: 500; pointer-events: none; z-index: 50; }
    .tooltip-arrow { position: absolute; width: 8px; height: 8px; background: inherit; transform: rotate(45deg); }
</style>
<div class="flex min-h-[120px] items-center justify-center">
    <div class="flex items-center gap-3">
        <code class="rounded-md bg-violet-50 px-3 py-2 font-mono text-sm text-violet-900 dark:bg-violet-950 dark:text-violet-100">php bin/console debug:router</code>
        <button type="button" data-controller="clipboard tooltip" data-clipboard-source-value="php bin/console debug:router" data-action="clipboard#copy clipboard:copied->tooltip#show" data-tooltip-content-value="Copied!" data-tooltip-trigger-value="manual" data-tooltip-auto-hide-value="2000" class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-violet-500 active:scale-95 active:bg-violet-700">Copy</button>
    </div>
</div>
```

## API Reference

::: api-reference
