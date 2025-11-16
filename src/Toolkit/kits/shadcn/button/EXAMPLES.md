# Examples

## Default

```twig {"preview":true}
<twig:Button>
    Click me
</twig:Button>
```

## Primary

```twig {"preview":true}
<twig:Button>Button</twig:Button>
```

## Secondary

```twig {"preview":true}
<twig:Button variant="secondary">Secondary</twig:Button>
```

## Destructive

```twig {"preview":true}
<twig:Button variant="destructive">Destructive</twig:Button>
```

## Outline

```twig {"preview":true}
<twig:Button variant="outline">Outline</twig:Button>
```

## Ghost

```twig {"preview":true}
<twig:Button variant="ghost">Ghost</twig:Button>
```

## Link

```twig {"preview":true}
<twig:Button variant="link">Link</twig:Button>
```

## Icon

```twig {"preview":true}
<twig:Button variant="outline" size="icon">
    <twig:ux:icon name="lucide:circle-fading-arrow-up" />
</twig:Button>
```

## With Icon

```twig {"preview":true}
<twig:Button>
    <twig:ux:icon name="lucide:mail" /> Login with Email
</twig:Button>
```

## Loading

```twig {"preview":true}
<twig:Button disabled>
    <twig:ux:icon name="lucide:loader-2" class="animate-spin" /> Please wait
</twig:Button>
```

## Different sizes

```twig {"preview":true}
<div class="flex flex-col items-start gap-8 sm:flex-row">
    <div class="flex items-center gap-2">
        <twig:Button size="sm" variant="outline">Small</twig:Button>
        <twig:Button size="icon-sm" variant="outline" aria-label="Submit">
            <twig:ux:icon name="lucide:arrow-up-right" class="size-4" />
        </twig:Button>
    </div>
    <div class="flex items-center gap-2">
        <twig:Button variant="outline">Default</twig:Button>
        <twig:Button variant="outline" aria-label="Submit">
            <twig:ux:icon name="lucide:arrow-up-right" class="size-4" />
        </twig:Button>
    </div>
    <div class="flex items-center gap-2">
        <twig:Button size="lg" variant="outline">Large</twig:Button>
        <twig:Button size="icon-lg" variant="outline" aria-label="Submit">
            <twig:ux:icon name="lucide:arrow-up-right" class="size-4" />
        </twig:Button>
    </div>
</div>
```

## As link

```twig {"preview":true}
<twig:Button as="a" href="https://example.com">Link</twig:Button>
```
