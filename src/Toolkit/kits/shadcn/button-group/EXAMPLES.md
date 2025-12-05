# Examples

## Default

```twig {"preview":true,"height":"220px"}
<twig:ButtonGroup>
    <twig:ButtonGroup class="hidden sm:flex">
        <twig:Button variant="outline" size="icon" aria-label="Go back">
            <twig:ux:icon name="lucide:arrow-left" class="size-4" />
        </twig:Button>
    </twig:ButtonGroup>
    <twig:ButtonGroup>
        <twig:Button variant="outline">Archive</twig:Button>
        <twig:Button variant="outline">Report</twig:Button>
    </twig:ButtonGroup>
    <twig:ButtonGroup>
        <twig:Button variant="outline">
            <twig:ux:icon name="lucide:clock" class="size-4" />
            Snooze
        </twig:Button>
        <twig:Button variant="outline" size="icon" aria-label="More options">
            <twig:ux:icon name="lucide:more-horizontal" class="size-4" />
        </twig:Button>
    </twig:ButtonGroup>
</twig:ButtonGroup>
```

## Orientation

```twig {"preview":true,"height":"200px"}
<twig:ButtonGroup orientation="vertical" aria-label="Media controls" class="h-fit">
    <twig:Button variant="outline" size="icon">
        <twig:ux:icon name="lucide:plus" class="size-4" />
    </twig:Button>
    <twig:Button variant="outline" size="icon">
        <twig:ux:icon name="lucide:minus" class="size-4" />
    </twig:Button>
</twig:ButtonGroup>
```

## Size

```twig {"preview":true,"height":"420px"}
<div class="flex flex-col items-start gap-8">
    <twig:ButtonGroup>
        <twig:Button variant="outline" size="sm">Small</twig:Button>
        <twig:Button variant="outline" size="sm">Button</twig:Button>
        <twig:Button variant="outline" size="sm">Group</twig:Button>
        <twig:Button variant="outline" size="icon-sm">
            <twig:ux:icon name="lucide:plus" class="size-4" />
        </twig:Button>
    </twig:ButtonGroup>
    <twig:ButtonGroup>
        <twig:Button variant="outline">Default</twig:Button>
        <twig:Button variant="outline">Button</twig:Button>
        <twig:Button variant="outline">Group</twig:Button>
        <twig:Button variant="outline" size="icon">
            <twig:ux:icon name="lucide:plus" class="size-4" />
        </twig:Button>
    </twig:ButtonGroup>
    <twig:ButtonGroup>
        <twig:Button variant="outline" size="lg">Large</twig:Button>
        <twig:Button variant="outline" size="lg">Button</twig:Button>
        <twig:Button variant="outline" size="lg">Group</twig:Button>
        <twig:Button variant="outline" size="icon-lg">
            <twig:ux:icon name="lucide:plus" class="size-5" />
        </twig:Button>
    </twig:ButtonGroup>
</div>
```

## Input

```twig {"preview":true,"height":"200px"}
<twig:ButtonGroup class="max-w-md">
    <twig:Input placeholder="Search..." />
    <twig:Button size="icon-lg" variant="outline" aria-label="Search">
        <twig:ux:icon name="lucide:search" class="size-4" />
    </twig:Button>
</twig:ButtonGroup>
```

## Nested

```twig {"preview":true,"height":"240px"}
<twig:ButtonGroup>
    <twig:ButtonGroup>
        <twig:Button variant="outline" size="sm">1</twig:Button>
        <twig:Button variant="outline" size="sm">2</twig:Button>
        <twig:Button variant="outline" size="sm">3</twig:Button>
        <twig:Button variant="outline" size="sm">4</twig:Button>
        <twig:Button variant="outline" size="sm">5</twig:Button>
    </twig:ButtonGroup>
    <twig:ButtonGroup>
        <twig:Button variant="outline" size="icon-sm" aria-label="Previous">
            <twig:ux:icon name="lucide:arrow-left" class="size-4" />
        </twig:Button>
        <twig:Button variant="outline" size="icon-sm" aria-label="Next">
            <twig:ux:icon name="lucide:arrow-right" class="size-4" />
        </twig:Button>
    </twig:ButtonGroup>
</twig:ButtonGroup>
```

## Separator

```twig {"preview":true,"height":"200px"}
<twig:ButtonGroup>
    <twig:Button variant="secondary" size="sm">Copy</twig:Button>
    <twig:ButtonGroup:Separator />
    <twig:Button variant="secondary" size="sm">Paste</twig:Button>
</twig:ButtonGroup>
```

## Split

```twig {"preview":true,"height":"200px"}
<twig:ButtonGroup>
    <twig:Button variant="secondary">Button</twig:Button>
    <twig:ButtonGroup:Separator />
    <twig:Button size="icon" variant="secondary">
        <twig:ux:icon name="tabler:plus" class="size-4" />
    </twig:Button>
</twig:ButtonGroup>
```
