# Checkbox

A form control that allows the user to toggle between checked and unchecked states.

```twig {"preview":true}
<div class="flex items-center space-x-2">
    <twig:Checkbox id="terms" />
    <label for="terms" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
        Accept terms and conditions
    </label>
</div>
```

## Installation

<!-- Placeholder: Installation -->

## Usage

<!-- Placeholder: Usage -->

## Examples

### Default

```twig {"preview":true}
<div class="flex items-center space-x-2">
    <twig:Checkbox id="terms" />
    <label for="terms" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
        Accept terms and conditions
    </label>
</div>
```

### With Label Component

```twig {"preview":true}
<div class="flex items-center space-x-2">
    <twig:Checkbox id="terms" />
    <twig:Label for="terms">Accept terms and conditions</twig:Label>
</div>
```

### Disabled

```twig {"preview":true}
<div class="flex items-center space-x-2">
    <twig:Checkbox id="terms" disabled />
    <twig:Label for="terms">Accept terms and conditions</twig:Label>
</div>
```
