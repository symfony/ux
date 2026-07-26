# Button Group

The button group component from Flowbite can be used to stack together multiple buttons and links inside a single element.

```twig {"preview":true}
<twig:ButtonGroup>
    <twig:Button variant="tertiary" size="sm">Profile</twig:Button>
    <twig:Button variant="tertiary" size="sm">Settings</twig:Button>
    <twig:Button variant="tertiary" size="sm">Messages</twig:Button>
</twig:ButtonGroup>
```

## Installation

::: installation

## Usage

```twig
<twig:ButtonGroup>
    <twig:Button variant="tertiary">One</twig:Button>
    <twig:Button variant="tertiary">Two</twig:Button>
</twig:ButtonGroup>
```

## Accessibility

- The `ButtonGroup` component has the `role` attribute set to `group`.
- Use `Tab` to navigate between the buttons in the group.

## Examples

### Orientation

```twig {"preview":true}
<twig:ButtonGroup orientation="vertical" class="w-56">
    <twig:Button variant="tertiary">Profile</twig:Button>
    <twig:Button variant="tertiary">Settings</twig:Button>
    <twig:Button variant="tertiary">Messages</twig:Button>
</twig:ButtonGroup>
```

### Size

```twig {"preview":true}
<div class="flex flex-col items-start gap-8">
    <twig:ButtonGroup>
        <twig:Button variant="tertiary" size="sm">Small</twig:Button>
        <twig:Button variant="tertiary" size="sm">Button</twig:Button>
        <twig:Button variant="tertiary" size="sm">Group</twig:Button>
    </twig:ButtonGroup>
    <twig:ButtonGroup>
        <twig:Button variant="tertiary">Default</twig:Button>
        <twig:Button variant="tertiary">Button</twig:Button>
        <twig:Button variant="tertiary">Group</twig:Button>
    </twig:ButtonGroup>
    <twig:ButtonGroup>
        <twig:Button variant="tertiary" size="lg">Large</twig:Button>
        <twig:Button variant="tertiary" size="lg">Button</twig:Button>
        <twig:Button variant="tertiary" size="lg">Group</twig:Button>
    </twig:ButtonGroup>
</div>
```

## API Reference

::: api-reference
