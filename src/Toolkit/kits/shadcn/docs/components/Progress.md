# Progress

A component for displaying progress of a task or operation.

```twig {"preview":true}
<twig:Progress value="33" />
```

## Installation

<!-- Placeholder: Installation -->

## Usage

<!-- Placeholder: Usage -->

## Examples

### Default

```twig {"preview":true}
<twig:Progress value="33" />
```

### With Label

```twig {"preview":true}
<div class="flex flex-col gap-2 w-sm">
    <div class="flex items-center justify-between">
        <twig:Label>Loading</twig:Label>
        <span class="text-sm text-muted-foreground">33%</span>
    </div>
    <twig:Progress value="33" />
</div>
```

### Different Values

```twig {"preview":true}
<div class="flex flex-col gap-1.5 w-sm">
    <twig:Progress value="0" />
    <twig:Progress value="25" />
    <twig:Progress value="50" />
    <twig:Progress value="75" />
    <twig:Progress value="100" />
</div>
```
