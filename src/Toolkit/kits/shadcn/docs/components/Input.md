# Input

A form control that allows users to enter text, numbers, or select files.

```twig {"preview":true}
<twig:Input type="email" placeholder="Email" class="max-w-sm" />
```

## Installation

<!-- Placeholder: Installation -->

## Usage

<!-- Placeholder: Usage -->

## Examples

### Default

```twig {"preview":true}
<twig:Input type="email" placeholder="Email" class="max-w-sm" />
```

### File

```twig {"preview":true}
<div class="grid w-full max-w-sm items-center gap-1.5">
    <label for="picture">Picture</label>
    <twig:Input type="file" id="picture" />
</div>
```

### Disabled

```twig {"preview":true}
<twig:Input type="email" placeholder="Email" disabled class="max-w-sm" />
```

### With Label

```twig {"preview":true}
<div class="grid w-full max-w-sm items-center gap-1.5">
    <twig:Label for="email">Email</twig:Label>
    <twig:Input type="email" id="email" />
</div>
```

### With Button

```twig {"preview":true}
<div class="flex w-full max-w-sm items-center space-x-2">
    <twig:Input type="email" id="email" />
    <twig:Button type="submit">Subscribe</twig:Button>
</div>
```
