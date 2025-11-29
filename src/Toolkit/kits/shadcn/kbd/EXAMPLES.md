# Examples

## Default

```twig {"preview":true}
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

## Group

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

## Button

Use the `Kbd` component inside a `Button` component to display a keyboard key inside a button.

```twig {"preview":true}
<div class="flex flex-wrap items-center gap-4">
  <twig:Button size="sm" class="pr-2">
    Accept <twig:Kbd>⏎</twig:Kbd>
  </twig:Button>
  <twig:Button variant="outline" size="sm" class="pr-2">
    Cancel <twig:Kbd>Esc</twig:Kbd>
  </twig:Button>
</div>
```
