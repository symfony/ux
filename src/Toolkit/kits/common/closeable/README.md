# Closeable

A Stimulus behavior that removes its element from the page when dismissed, with optional delayed and automatic closing and an animated countdown bar.

```twig {"preview":true,"height":"140px","collapseClass":true}
<div data-controller="closeable" class="flex items-start gap-3 rounded-md border border-violet-200 bg-violet-50 p-4 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">
    <div class="flex-1">
        <p class="font-medium">Heads up!</p>
        <p class="text-violet-700 dark:text-violet-300">This message can be dismissed.</p>
    </div>
    <button type="button" data-action="click->closeable#close" aria-label="Dismiss" class="text-violet-500 hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-200">
        <span aria-hidden="true" class="text-lg leading-none">&times;</span>
    </button>
</div>
```

## Installation

::: installation

## Usage

Add `data-controller="closeable"` to the element you want to remove, then trigger `closeable#close` from a child element:

```twig
<div data-controller="closeable">
    <button type="button" data-action="click->closeable#close">Dismiss</button>
</div>
```

## Examples

### Delayed Close

Set a `data-closeable-delay-param` (in milliseconds) on the close action to defer the removal. Add a `timerbar` target to visualize the countdown.

```twig {"preview":true,"height":"140px","collapseClass":true}
<div data-controller="closeable" class="relative flex items-start gap-3 overflow-hidden rounded-md border border-violet-200 bg-violet-50 p-4 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">
    <div class="flex-1">
        <p class="font-medium">Saved!</p>
        <p class="text-violet-700 dark:text-violet-300">Dismiss to close after a short delay.</p>
    </div>
    <button type="button" data-action="click->closeable#close" data-closeable-delay-param="3000" aria-label="Dismiss" class="text-violet-500 hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-200">
        <span aria-hidden="true" class="text-lg leading-none">&times;</span>
    </button>
    <div data-closeable-target="timerbar" class="absolute bottom-0 left-0 h-1 w-full bg-violet-500 transition-[width] ease-linear dark:bg-violet-400"></div>
</div>
```

### Auto Close

Set `data-closeable-auto-close-value` (in milliseconds) to remove the element automatically once it connects. The `timerbar` target animates down over the same duration.

```twig {"preview":true,"height":"140px","collapseClass":true}
<div data-controller="closeable" data-closeable-auto-close-value="5000" class="relative flex items-start gap-3 overflow-hidden rounded-md border border-violet-200 bg-violet-50 p-4 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">
    <div class="flex-1">
        <p class="font-medium">Copied to clipboard</p>
        <p class="text-violet-700 dark:text-violet-300">This message closes on its own.</p>
    </div>
    <button type="button" data-action="click->closeable#close" aria-label="Dismiss" class="text-violet-500 hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-200">
        <span aria-hidden="true" class="text-lg leading-none">&times;</span>
    </button>
    <div data-closeable-target="timerbar" class="absolute bottom-0 left-0 h-1 w-full bg-violet-500 transition-[width] ease-linear dark:bg-violet-400"></div>
</div>
```

### Cancel Auto Close

Call `closeable#cancel` to stop a pending close. Here, hovering the message cancels the automatic close so the user has time to read it.

```twig {"preview":true,"height":"140px","collapseClass":true}
<div data-controller="closeable" data-closeable-auto-close-value="5000" data-action="mouseenter->closeable#cancel" class="relative flex items-start gap-3 overflow-hidden rounded-md border border-violet-200 bg-violet-50 p-4 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">
    <div class="flex-1">
        <p class="font-medium">Hover to keep me</p>
        <p class="text-violet-700 dark:text-violet-300">Move your pointer over this message to cancel the automatic close.</p>
    </div>
    <button type="button" data-action="click->closeable#close" aria-label="Dismiss" class="text-violet-500 hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-200">
        <span aria-hidden="true" class="text-lg leading-none">&times;</span>
    </button>
    <div data-closeable-target="timerbar" class="absolute bottom-0 left-0 h-1 w-full bg-violet-500 transition-[width] ease-linear dark:bg-violet-400"></div>
</div>
```

## API Reference

::: api-reference
