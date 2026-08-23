# Scroll Area

Augments the native scroll functionality for custom, cross-browser styling.

```twig {"preview":true}
<twig:ScrollArea class="h-72 w-48 rounded-md border">
    <div class="p-4">
        <h4 class="mb-4 text-sm leading-none font-medium">Tags</h4>
        {% for i in range(50, 1, -1) %}
            <div class="text-sm">v1.2.0-beta.{{ i }}</div>
            <twig:Separator class="my-2" />
        {% endfor %}
    </div>
</twig:ScrollArea>
```

## Installation

::: installation

## Usage

```twig
<twig:ScrollArea class="h-[200px] w-[350px] rounded-md border p-4">
    Your scrollable content here.
</twig:ScrollArea>
```

## Examples

### Horizontal

```twig {"preview":true}
<twig:ScrollArea class="w-96 rounded-md border whitespace-nowrap">
    <div class="flex w-max space-x-4 p-4">
        <figure class="shrink-0">
            <div class="overflow-hidden rounded-md">
                <img src="https://images.unsplash.com/photo-1465869185982-5a1a7522cbcb?auto=format&fit=crop&w=300&q=80" alt="Photo by Ornella Binni" class="aspect-[3/4] h-fit w-fit object-cover" width="300" height="400">
            </div>
            <figcaption class="pt-2 text-xs text-muted-foreground">
                Photo by
                <span class="font-semibold text-foreground">Ornella Binni</span>
            </figcaption>
        </figure>
        <figure class="shrink-0">
            <div class="overflow-hidden rounded-md">
                <img src="https://images.unsplash.com/photo-1548516173-3cabfa4607e9?auto=format&fit=crop&w=300&q=80" alt="Photo by Tom Byrom" class="aspect-[3/4] h-fit w-fit object-cover" width="300" height="400">
            </div>
            <figcaption class="pt-2 text-xs text-muted-foreground">
                Photo by
                <span class="font-semibold text-foreground">Tom Byrom</span>
            </figcaption>
        </figure>
        <figure class="shrink-0">
            <div class="overflow-hidden rounded-md">
                <img src="https://images.unsplash.com/photo-1494337480532-3725c85fd2ab?auto=format&fit=crop&w=300&q=80" alt="Photo by Vladimir Malyavko" class="aspect-[3/4] h-fit w-fit object-cover" width="300" height="400">
            </div>
            <figcaption class="pt-2 text-xs text-muted-foreground">
                Photo by
                <span class="font-semibold text-foreground">Vladimir Malyavko</span>
            </figcaption>
        </figure>
    </div>
</twig:ScrollArea>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col items-center gap-8">
    {# Arabic #}
    <twig:ScrollArea dir="rtl" class="h-72 w-48 rounded-md border">
        <div class="p-4">
            <h4 class="mb-4 text-sm leading-none font-medium">العلامات</h4>
            {% for i in range(50, 1, -1) %}
                <div class="text-sm">v1.2.0-beta.{{ i }}</div>
                <twig:Separator class="my-2" />
            {% endfor %}
        </div>
    </twig:ScrollArea>

    {# Hebrew #}
    <twig:ScrollArea dir="rtl" class="h-72 w-48 rounded-md border">
        <div class="p-4">
            <h4 class="mb-4 text-sm leading-none font-medium">תגיות</h4>
            {% for i in range(50, 1, -1) %}
                <div class="text-sm">v1.2.0-beta.{{ i }}</div>
                <twig:Separator class="my-2" />
            {% endfor %}
        </div>
    </twig:ScrollArea>
</div>
```

## API Reference

::: api-reference
