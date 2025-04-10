# Button

A button component that can be used to trigger actions or events.

```twig {"preview":true}
<twig:Button>
    Click me
</twig:Button>
```

## Installation

<!-- Placeholder: Installation -->

## Usage

<!-- Placeholder: Usage -->

## Examples

### Default

```twig {"preview":true}
<twig:Button>
    Click me
</twig:Button>
```

### Primary

```twig {"preview":true}
<twig:Button>Button</twig:Button>
``` 

### Secondary

```twig {"preview":true}
<twig:Button variant="outline">Outline</twig:Button>
``` 

### Destructive

```twig {"preview":true}
<twig:Button variant="destructive">Destructive</twig:Button>
``` 

### Outline

```twig {"preview":true}
<twig:Button variant="outline">Outline</twig:Button>
``` 

### Ghost

```twig {"preview":true}
<twig:Button variant="ghost">Ghost</twig:Button>
``` 

### Link

```twig {"preview":true}
<twig:Button variant="link">Link</twig:Button>
``` 

### Icon

```twig {"preview":true}
<twig:Button variant="outline" size="icon">
    <twig:ux:icon name="lucide:chevron-right" />
</twig:Button>
``` 

### With Icon

```twig {"preview":true}
<twig:Button>
    <twig:ux:icon name="lucide:mail" /> Login with Email
</twig:Button>
``` 

### Loading

```twig {"preview":true}
<twig:Button disabled>
    <twig:ux:icon name="lucide:loader-2" class="animate-spin" /> Please wait
</twig:Button>
``` 
