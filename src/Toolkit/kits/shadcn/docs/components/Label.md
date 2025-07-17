# Label

A text element that identifies form controls and other content.

```twig {"preview":true}
<div class="flex items-center space-x-2">
    <twig:Checkbox id="terms" />
    <twig:Label for="terms">Accept terms and conditions</twig:Label>
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
    <twig:Label for="terms">Accept terms and conditions</twig:Label>
</div>
```

### With Input

```twig {"preview":true}
<div class="grid w-full max-w-sm items-center gap-1.5">
    <twig:Label for="email">Email</twig:Label>
    <twig:Input type="email" id="email" placeholder="Enter your email" />
</div>
```

### Required Field

```twig {"preview":true}
<div class="grid w-full max-w-sm items-center gap-1.5">
    <twig:Label for="email" class="after:content-['*'] after:ml-0.5 after:text-red-500">Email</twig:Label>
    <twig:Input type="email" id="email" placeholder="Enter your email" />
</div>
```
