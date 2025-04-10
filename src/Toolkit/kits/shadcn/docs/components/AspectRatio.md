# AspectRatio

The AspectRatio component is a component that allows you to display an element with a specific aspect ratio.

```twig {"preview":true,"height":"400px"}
<twig:AspectRatio ratio="1 / 1" class="max-w-[300px]">
    <img 
        src="https://images.unsplash.com/photo-1535025183041-0991a977e25b?w=300&amp;dpr=2&amp;q=80" 
        alt="Landscape photograph by Tobias Tullius"
        class="h-full w-full rounded-md object-cover"
    />
</twig:AspectRatio>
```

## Installation

<!-- Placeholder: Installation -->

## Usage

<!-- Placeholder: Usage -->

## Examples

### With a 1 / 1 aspect ratio

```twig {"preview":true,"height":"400px"}
<twig:AspectRatio ratio="1 / 1" class="max-w-[350px]">
    <img 
        src="https://images.unsplash.com/photo-1535025183041-0991a977e25b?w=300&amp;dpr=2&amp;q=80" 
        alt="Landscape photograph by Tobias Tullius"
        class="h-full w-full rounded-md object-cover"
    />
</twig:AspectRatio>
```

### With a 16 / 9 aspect ratio

```twig {"preview":true,"height":"400px"}
<twig:AspectRatio ratio="16 / 9" class="max-w-[350px]">
    <img 
        src="https://images.unsplash.com/photo-1535025183041-0991a977e25b?w=300&amp;dpr=2&amp;q=80" 
        alt="Landscape photograph by Tobias Tullius"
        class="h-full w-full rounded-md object-cover"
    />
</twig:AspectRatio>
```
