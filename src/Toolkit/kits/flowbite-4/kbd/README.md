# Kbd

The KBD (Keyboard) component can be used to indicate a textual user input from the keyboard inside other elements such as in text, tables, cards, and more.

```twig {"preview":true}
<div>
    <twig:Kbd>Shift</twig:Kbd>
    <twig:Kbd>Ctrl</twig:Kbd>
    <twig:Kbd>Tab</twig:Kbd>
    <twig:Kbd>Caps Lock</twig:Kbd>
    <twig:Kbd>Esc</twig:Kbd>
    <twig:Kbd>Spacebar</twig:Kbd>
    <twig:Kbd>Enter</twig:Kbd>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Kbd>Ctrl</twig:Kbd>
```

## Examples

### Arrow keys

Use this example to show arrow keys inside the KBD styled element.

```twig {"preview":true}
<div>
    <twig:Kbd>
        <twig:ux:icon name="flowbite:caret-up-solid" class="h-3 w-3" aria-hidden="true" />
        <span class="sr-only">Arrow key up</span>
    </twig:Kbd>

    <twig:Kbd>
        <twig:ux:icon name="flowbite:caret-down-solid" class="h-3 w-3" aria-hidden="true" />
        <span class="sr-only">Arrow key down</span>
    </twig:Kbd>

    <twig:Kbd>
        <twig:ux:icon name="flowbite:caret-left-solid" class="h-3 w-3" aria-hidden="true" />
        <span class="sr-only">Arrow key left</span>
    </twig:Kbd>

    <twig:Kbd>
        <twig:ux:icon name="flowbite:caret-right-solid" class="h-3 w-3" aria-hidden="true" />
        <span class="sr-only">Arrow key right</span>
    </twig:Kbd>
</div>
```

## API Reference

::: api-reference
