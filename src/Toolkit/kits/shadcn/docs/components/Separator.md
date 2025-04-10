# Separator

A component for creating visual separators between content.

```twig {"preview":true}
<div class="max-w-sm">
    <div class="space-y-1">
        <h4 class="text-sm font-medium leading-none">Symfony UX</h4>
        <p class="text-sm text-muted-foreground">
            Symfony UX initiative: a JavaScript ecosystem for Symfony
        </p>
    </div>
    <twig:Separator class="my-4" />
    <div class="flex h-5 items-center space-x-4 text-sm">
        <a href="https://ux.symfony.com" class="hover:underline">Website</a>
        <twig:Separator orientation="vertical" />
        <a href="https://ux.symfony.com/packages" class="hover:underline">Packages</a>
        <twig:Separator orientation="vertical" />
        <a href="https://github.com/symfony/ux" class="hover:underline">Source</a>
    </div>
</div>
```

## Installation

<!-- Placeholder: Installation -->

## Usage

<!-- Placeholder: Usage -->

## Examples

### Default

```twig {"preview":true}
<div>
    <div class="space-y-1">
        <h4 class="text-sm font-medium leading-none">Symfony UX</h4>
        <p class="text-sm text-muted-foreground">
            Symfony UX initiative: a JavaScript ecosystem for Symfony
        </p>
    </div>
    <twig:Separator class="my-4" />
    <div class="flex h-5 items-center space-x-4 text-sm">
        <div>Blog</div>
        <twig:Separator orientation="vertical" />
        <div>Docs</div>
        <twig:Separator orientation="vertical" />
        <div>Source</div>
    </div>
</div>
```

### Vertical

```twig {"preview":true}
<div class="flex h-5 items-center gap-4 text-sm">
    <div>Blog</div>
    <twig:Separator orientation="vertical" />
    <div>Docs</div>
    <twig:Separator orientation="vertical" />
    <div>Source</div>
</div>
```
