# Checkbox

The checkbox component can be used to receive one or more selected options from the user in the form of a square box available in multiple styles, sizes, colors, and variants coded with the utility classes from Tailwind CSS and with support for dark mode.

```twig {"preview":true}
<div class="flex flex-col gap-3">
    <div class="flex items-center mb-4">
        <twig:Checkbox id="default"/>
        <twig:Label for="default" class="select-none ms-2 font-medium">Default checkbox</twig:Label>
    </div>

    <div class="flex items-center mb-4">
        <twig:Checkbox id="checked" checked/>
        <twig:Label for="checked" class="select-none ms-2 font-medium">Checked state</twig:Label>
    </div>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Checkbox />
```

## Examples

### Disabled

This example can be used for the disabled state of the checkbox component by applying the disabled attribute to the input element.

```twig {"preview":true}
<div class="flex flex-col gap-3">
    <div class="flex items-center mb-4">
        <twig:Checkbox id="default" disabled/>
        <twig:Label for="default" class="select-none ms-2 font-medium text-fg-disabled">Default checkbox</twig:Label>
    </div>

    <div class="flex items-center mb-4">
        <twig:Checkbox id="checked" checked disabled/>
        <twig:Label for="checked" class="select-none ms-2 font-medium text-fg-disabled">Checked state</twig:Label>
    </div>
</div>
```

### Bordered

This example can be used to create a checkbox component with a bordered style, a description text and an icon.

```twig {"preview":true}
<div class="grid gap-6 md:grid-cols-2">

    <div class="flex items-center mb-4">
        <twig:Label for="default" class="flex justify-between space-x-2.5 bg-neutral-primary-soft border border-default rounded-base shadow-xs">
            <div class="p-4">
                <twig:ux:icon name="flowbite:server-outline" class="w-7 h-7 text-body mb-1.5" aria-hidden="true" />
                <p class="select-none w-full text-sm font-medium text-heading">16GB unified memory</p>
                <p class="select-none text-sm text-body">Seamlessly handle multitasking, large apps.</p>
            </div>
            <twig:Checkbox id="default" class="mt-4 me-4"/>
        </twig:Label>
    </div>

    <div class="flex items-center mb-4">
        <twig:Label for="checked" class="flex justify-between space-x-2.5 bg-neutral-primary-soft border border-default rounded-base shadow-xs">
            <div class="p-4">
                <twig:ux:icon name="flowbite:database-outline" class="w-7 h-7 text-body mb-1.5" aria-hidden="true" />
                <p class="select-none w-full text-sm font-medium text-heading">1TB SSD storage</p>
                <p class="select-none text-sm text-body">Get ultra-fast storage with 1TB of SSD space</p>
            </div>
            <twig:Checkbox id="checked" class="mt-4 me-4" checked/>
        </twig:Label>
    </div>
</div>
```

## API Reference

::: api-reference
