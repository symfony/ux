# Examples

## Default

```twig {"preview":true,"height":"200px"}
<twig:Tooltip>
    <twig:Tooltip:Trigger as="span">
        <twig:Button variant="outline">Hover me</twig:Button>
    </twig:Tooltip:Trigger>
    <twig:Tooltip:Content>
        <p>Add to library</p>
    </twig:Tooltip:Content>
</twig:Tooltip>
```

## Sides

```twig {"preview":true,"height":"300px"}
<div class="flex flex-col gap-4 items-center justify-center">
    <twig:Tooltip>
        <twig:Tooltip:Trigger as="span">
            <twig:Button variant="outline">Top (default)</twig:Button>
        </twig:Tooltip:Trigger>
        <twig:Tooltip:Content side="top">
            Tooltip on top
        </twig:Tooltip:Content>
    </twig:Tooltip>

    <div class="flex gap-4">
        <twig:Tooltip>
            <twig:Tooltip:Trigger as="span">
                <twig:Button variant="outline">Left</twig:Button>
            </twig:Tooltip:Trigger>
            <twig:Tooltip:Content side="left">
                Tooltip on left
            </twig:Tooltip:Content>
        </twig:Tooltip>

        <twig:Tooltip>
            <twig:Tooltip:Trigger as="span">
                <twig:Button variant="outline">Right</twig:Button>
            </twig:Tooltip:Trigger>
            <twig:Tooltip:Content side="right">
                Tooltip on right
            </twig:Tooltip:Content>
        </twig:Tooltip>
    </div>

    <twig:Tooltip>
        <twig:Tooltip:Trigger as="span">
            <twig:Button variant="outline">Bottom</twig:Button>
        </twig:Tooltip:Trigger>
        <twig:Tooltip:Content side="bottom">
            Tooltip on bottom
        </twig:Tooltip:Content>
    </twig:Tooltip>
</div>
```

## With Icon

```twig {"preview":true,"height":"200px"}
<twig:Tooltip>
    <twig:Tooltip:Trigger as="span">
        <twig:Button variant="ghost" size="icon">
            <twig:ux:icon name="lucide:info" class="size-4" />
        </twig:Button>
    </twig:Tooltip:Trigger>
    <twig:Tooltip:Content>
        <p>This is helpful information</p>
    </twig:Tooltip:Content>
</twig:Tooltip>
```

## Custom Delay

```twig {"preview":true,"height":"200px"}
<twig:Tooltip delayDuration="200">
    <twig:Tooltip:Trigger as="span">
        <twig:Button variant="outline">Quick tooltip (200ms)</twig:Button>
    </twig:Tooltip:Trigger>
    <twig:Tooltip:Content>
        This appears quickly!
    </twig:Tooltip:Content>
</twig:Tooltip>
```

## On Text

```twig {"preview":true,"height":"200px"}
<div class="text-sm text-muted-foreground">
    You can use tooltips on
    <twig:Tooltip>
        <twig:Tooltip:Trigger as="span" class="underline decoration-dotted">
            inline text
        </twig:Tooltip:Trigger>
        <twig:Tooltip:Content>
            Like this!
        </twig:Tooltip:Content>
    </twig:Tooltip>
    to provide additional context.
</div>


```
