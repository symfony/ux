# Skeleton

Use to show a placeholder while content is loading.

```twig {"preview":true,"height":"200px"}
<div class="flex items-center gap-4">
    <twig:Skeleton class="h-12 w-12 rounded-full" />
    <div class="space-y-2">
        <twig:Skeleton class="h-4 w-[250px]" />
        <twig:Skeleton class="h-4 w-[200px]" />
    </div>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Skeleton class="h-[20px] w-[100px] rounded-full" />
```

## Examples

### Avatar

```twig {"preview":true}
<div class="flex w-fit items-center gap-4">
    <twig:Skeleton class="size-10 shrink-0 rounded-full" />
    <div class="grid gap-2">
        <twig:Skeleton class="h-4 w-[150px]" />
        <twig:Skeleton class="h-4 w-[100px]" />
    </div>
</div>
```

### Card

```twig {"preview":true,"height":"350px"}
<twig:Card class="w-full max-w-xs">
    <twig:Card:Header>
        <twig:Skeleton class="h-4 w-2/3" />
        <twig:Skeleton class="h-4 w-1/2" />
    </twig:Card:Header>
    <twig:Card:Content>
        <twig:Skeleton class="aspect-video w-full" />
    </twig:Card:Content>
</twig:Card>
```

### Text

```twig {"preview":true}
<div class="flex w-full max-w-xs flex-col gap-2">
    <twig:Skeleton class="h-4 w-full" />
    <twig:Skeleton class="h-4 w-full" />
    <twig:Skeleton class="h-4 w-3/4" />
</div>
```

### Form

```twig {"preview":true,"height":"300px"}
<div class="flex w-full max-w-xs flex-col gap-7">
    <div class="flex flex-col gap-3">
        <twig:Skeleton class="h-4 w-20" />
        <twig:Skeleton class="h-8 w-full" />
    </div>
    <div class="flex flex-col gap-3">
        <twig:Skeleton class="h-4 w-24" />
        <twig:Skeleton class="h-8 w-full" />
    </div>
    <twig:Skeleton class="h-8 w-24" />
</div>
```

### Table

```twig {"preview":true}
<div class="flex w-full max-w-sm flex-col gap-2">
    {% for i in 1..5 %}
        <div class="flex gap-4">
            <twig:Skeleton class="h-4 flex-1" />
            <twig:Skeleton class="h-4 w-24" />
            <twig:Skeleton class="h-4 w-20" />
        </div>
    {% endfor %}
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true,"height":"250px"}
<div class="flex flex-col gap-8">
    {# Arabic #}
    <div class="flex items-center gap-4" dir="rtl">
        <twig:Skeleton class="h-12 w-12 rounded-full" />
        <div class="space-y-2">
            <twig:Skeleton class="h-4 w-[250px]" />
            <twig:Skeleton class="h-4 w-[200px]" />
        </div>
    </div>

    {# Hebrew #}
    <div class="flex items-center gap-4" dir="rtl">
        <twig:Skeleton class="h-12 w-12 rounded-full" />
        <div class="space-y-2">
            <twig:Skeleton class="h-4 w-[250px]" />
            <twig:Skeleton class="h-4 w-[200px]" />
        </div>
    </div>
</div>
```

## API Reference

::: api-reference
