# Avatar

A component for displaying user profile images with a fallback for when the image is not available.

```twig {"preview":true}
<twig:Avatar>
    <twig:Avatar:Image src="https://github.com/symfony.png" alt="@symfony" />
</twig:Avatar>
```

## Installation

<!-- Placeholder: Installation -->

## Usage

<!-- Placeholder: Usage -->

## Examples

### Avatar with Image

```twig {"preview":true}
<twig:Avatar>
    <twig:Avatar:Image src="https://github.com/symfony.png" alt="@symfony" />
</twig:Avatar>
```

### Avatar with Text

```twig {"preview":true}
<div class="flex gap-1">
    <twig:Avatar>
        <twig:Avatar:Text>FP</twig:Avatar:Text>
    </twig:Avatar>
    <twig:Avatar>
        <twig:Avatar:Text class="bg-red-500 text-red-50">FP</twig:Avatar:Text>
    </twig:Avatar>
</div>
```

### Avatar Group

```twig {"preview":true}
<div class="flex -space-x-2">
    <twig:Avatar>
        <twig:Avatar:Image src="https://github.com/symfony.png" alt="@symfony" />
    </twig:Avatar>
    <twig:Avatar>
        <twig:Avatar:Text>FP</twig:Avatar:Text>
    </twig:Avatar>
    <twig:Avatar>
        <twig:Avatar:Text class="bg-red-500 text-red-50">FP</twig:Avatar:Text>
    </twig:Avatar>
</div>
``` 
