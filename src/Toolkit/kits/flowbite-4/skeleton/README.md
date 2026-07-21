# Skeleton

Use the skeleton component to indicate a loading status with placeholder elements that look very similar to the type of content that is being loaded such as paragraphs, heading, images, videos, and more.

```twig {"preview":true}
<div class="w-full max-w-sm mx-auto">
    <div role="status">
        <twig:Skeleton class="h-2.5 w-48 mb-4" />
        <div class="space-y-2.5">
            <twig:Skeleton class="h-2 max-w-[360px]" />
            <twig:Skeleton class="h-2 w-[200px]" />
            <twig:Skeleton class="h-2 max-w-[330px]" />
            <twig:Skeleton class="h-2 max-w-[300px]" />
            <twig:Skeleton class="h-2 max-w-[360px]" />
        </div>
        <span class="sr-only">Loading...</span>
    </div>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Skeleton class="h-[20px] w-[100px]" />
```

## Examples

### Card placeholder

Use this example to show a placeholder when loading content inside a card.

```twig {"preview":true,"height":"400px"}
<twig:Card class="animate-pulse" role="status">
    <twig:Card:Header>
        <twig:Skeleton class="flex items-center justify-center h-48 max-w-sm" shape="rounded" role="status">
            <twig:ux:icon name="flowbite:image-outline" class="w-11 h-11 text-fg-disabled" aria-hidden="true" />
            <span class="sr-only">Loading...</span>
        </twig:Skeleton>
    </twig:Card:Header>

    <twig:Card:Content>
        <twig:Skeleton class="h-2.5 w-48 mb-4" />

        <div class="space-y-2.5">
            <twig:Skeleton class="h-2" />
            <twig:Skeleton class="h-2" />
            <twig:Skeleton class="h-2" />
        </div>

        <div class="flex items-center mt-4">
            <twig:ux:icon name="flowbite:user-circle-outline" class="w-8 h-8 text-fg-disabled me-3" aria-hidden="true" />
            <div>
                <twig:Skeleton class="h-2.5 w-32 mb-2" />
                <twig:Skeleton class="w-48 h-2" />
            </div>
        </div>
        <span class="sr-only">Loading...</span>
    </twig:Card:Content>
</twig:Card>
```

## API Reference

::: api-reference
