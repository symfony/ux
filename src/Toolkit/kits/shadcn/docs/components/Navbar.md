# Navbar

The Navbar component provides a navigation bar for your application, with support for custom content and styling.

## Examples

### Basic Navbar

```twig
<twig:Navbar>
    <div class="flex items-center justify-between">
        <div class="text-xl font-bold">Logo</div>
        <div class="space-x-4">
            <a href="/">Home</a>
            <a href="/about">About</a>
            <a href="/contact">Contact</a>
        </div>
    </div>
</twig:Navbar>
```

### Navbar with Custom Class

```twig
<twig:Navbar class="bg-white shadow-md">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between">
            <div class="text-xl font-bold">Logo</div>
            <div class="space-x-4">
                <a href="/">Home</a>
                <a href="/about">About</a>
                <a href="/contact">Contact</a>
            </div>
        </div>
    </div>
</twig:Navbar>
``` 
