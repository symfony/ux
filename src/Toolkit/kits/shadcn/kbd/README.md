# Kbd

Used to display textual user input from keyboard.

```twig {"preview":true,"height":"200px"}
<div class="flex flex-col items-center gap-4">
    <twig:KbdGroup>
        <twig:Kbd>⌘</twig:Kbd>
        <twig:Kbd>⇧</twig:Kbd>
        <twig:Kbd>⌥</twig:Kbd>
        <twig:Kbd>⌃</twig:Kbd>
    </twig:KbdGroup>
    <twig:KbdGroup>
        <twig:Kbd>Ctrl</twig:Kbd>
        <span>+</span>
        <twig:Kbd>B</twig:Kbd>
    </twig:KbdGroup>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Kbd>Ctrl</twig:Kbd>
```

## Examples

### Group

Use the `KbdGroup` component to group keyboard keys together.

```twig {"preview":true}
<div class="flex flex-col items-center gap-4">
  <p class="text-muted-foreground text-sm">
    Use
    <twig:KbdGroup>
      <twig:Kbd>Ctrl + B</twig:Kbd>
      <twig:Kbd>Ctrl + K</twig:Kbd>
    </twig:KbdGroup>
    to open the command palette
  </p>
</div>
```

### Button

Use the `Kbd` component inside a `Button` component to display a keyboard key inside a button.

```twig {"preview":true}
<twig:Button variant="outline">
    Accept <twig:Kbd data-icon="inline-end" class="translate-x-0.5">⏎</twig:Kbd>
</twig:Button>
```

### Tooltip

You can use the `Kbd` component inside a `Tooltip` component to display a tooltip with a keyboard key.

```twig {"preview":true}
<div class="flex flex-wrap gap-4">
    <twig:ButtonGroup>
        <twig:Tooltip id="tooltip-kbd-save">
            <twig:Tooltip:Trigger>
                <twig:Button {{ ...tooltip_trigger_attrs }} variant="outline" class="rounded-r-none">Save</twig:Button>
            </twig:Tooltip:Trigger>
            <twig:Tooltip:Content>
                Save Changes <twig:Kbd>S</twig:Kbd>
            </twig:Tooltip:Content>
        </twig:Tooltip>
        <twig:Tooltip id="tooltip-kbd-print">
            <twig:Tooltip:Trigger>
                <twig:Button {{ ...tooltip_trigger_attrs }} variant="outline" class="rounded-l-none border-l-0">Print</twig:Button>
            </twig:Tooltip:Trigger>
            <twig:Tooltip:Content>
                Print Document <twig:KbdGroup>
                    <twig:Kbd>Ctrl</twig:Kbd>
                    <twig:Kbd>P</twig:Kbd>
                </twig:KbdGroup>
            </twig:Tooltip:Content>
        </twig:Tooltip>
    </twig:ButtonGroup>
</div>
```

### Input Group

You can use the `Kbd` component inside an `InputGroup:Addon` component to display a keyboard key inside an input group.

```twig {"preview":true}
<div class="flex w-full max-w-xs flex-col gap-6">
    <twig:InputGroup>
        <twig:InputGroup:Input placeholder="Search..." />
        <twig:InputGroup:Addon>
            <twig:ux:icon name="lucide:search" />
        </twig:InputGroup:Addon>
        <twig:InputGroup:Addon align="inline-end">
            <twig:Kbd>⌘</twig:Kbd>
            <twig:Kbd>K</twig:Kbd>
        </twig:InputGroup:Addon>
    </twig:InputGroup>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true,"height":"300px"}
<div class="flex flex-col gap-8">
    {# Arabic #}
    <div class="flex flex-col items-center gap-4" dir="rtl">
        <twig:KbdGroup>
            <twig:Kbd>⌘</twig:Kbd>
            <twig:Kbd>⇧</twig:Kbd>
            <twig:Kbd>⌥</twig:Kbd>
            <twig:Kbd>⌃</twig:Kbd>
        </twig:KbdGroup>
        <twig:KbdGroup>
            <twig:Kbd>Ctrl</twig:Kbd>
            <span>+</span>
            <twig:Kbd>B</twig:Kbd>
        </twig:KbdGroup>
    </div>

    {# Hebrew #}
    <div class="flex flex-col items-center gap-4" dir="rtl">
        <twig:KbdGroup>
            <twig:Kbd>⌘</twig:Kbd>
            <twig:Kbd>⇧</twig:Kbd>
            <twig:Kbd>⌥</twig:Kbd>
            <twig:Kbd>⌃</twig:Kbd>
        </twig:KbdGroup>
        <twig:KbdGroup>
            <twig:Kbd>Ctrl</twig:Kbd>
            <span>+</span>
            <twig:Kbd>B</twig:Kbd>
        </twig:KbdGroup>
    </div>
</div>
```

## API Reference

::: api-reference
