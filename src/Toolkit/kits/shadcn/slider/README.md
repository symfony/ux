# Slider

An input where the user selects a value from within a given range.

```twig {"preview":true}
<twig:Slider name="demo" min="0" max="100" step="1" value="75" class="mx-auto w-full max-w-xs" />
```

## Installation

::: installation

## Usage

```twig
<twig:Slider name="volume" min="0" max="100" value="50" />
```

## Examples

### Range

```twig {"preview":true}
<twig:Slider name="range" min="0" max="100" step="5" value="25,50" labels="Min,Max" class="mx-auto w-full max-w-xs" />
```

### Multiple Thumbs

```twig {"preview":true}
<twig:Slider name="multi" min="0" max="100" step="10" value="10,20,70" labels="Low,Medium,High" class="mx-auto w-full max-w-xs" />
```

### Vertical

```twig {"preview":true}
<div class="mx-auto flex w-full max-w-xs items-center justify-center gap-6">
    <twig:Slider name="vertical-1" min="0" max="100" step="1" value="50" orientation="vertical" />
    <twig:Slider name="vertical-2" min="0" max="100" step="1" value="25" orientation="vertical" />
</div>
```

### Disabled

```twig {"preview":true}
<twig:Slider name="disabled" min="0" max="100" step="1" value="50" disabled class="mx-auto w-full max-w-xs" />
```

### Controlled

```twig {"preview":true}
<div class="mx-auto grid w-full max-w-xs gap-3" data-controller="slider-display">
    <div class="flex items-center justify-between gap-2">
        <twig:Label for="slider-controlled-temperature">Temperature</twig:Label>
        <span class="text-sm text-muted-foreground" data-slider-display-target="output">0.3, 0.7</span>
    </div>
    <twig:Slider id="slider-controlled-temperature" name="temperature" min="0" max="1" step="0.1" value="0.3,0.7" data-action="input->slider-display#update" />
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<twig:Slider dir="rtl" name="rtl-slider" min="0" max="100" step="1" value="75" class="mx-auto w-full max-w-xs" />
```

## API Reference

::: api-reference
