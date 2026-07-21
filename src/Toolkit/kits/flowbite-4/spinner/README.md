# Spinner

An indicator that can be used to show a loading state.

```twig {"preview":true}
<twig:Spinner class="size-10" />
```

## Installation

::: installation

## Usage

```twig
<twig:Button disabled>
    <twig:Spinner /> Please wait
</twig:Button>
```

## Examples

### Size

Change the size of the spinner component using the `h-{*}` and `w-{*}` or `size-{*}` utility classes from Tailwind CSS:

```twig {"preview":true}
<div class="flex items-center gap-6">
    <twig:Spinner />
    <twig:Spinner class="size-6" />
    <twig:Spinner class="size-8" />
</div>
```

### Color

You can change the colors of the spinner element using the fill and color utility classes from Tailwind CSS:<br>
use `text-{*}` to change the main colors

```twig {"preview":true}
<div class="flex items-center gap-6">
    <twig:Spinner class="text-brand size-8" />
    <twig:Spinner class="text-danger size-8" />
    <twig:Spinner class="text-success size-8" />
    <twig:Spinner class="text-warning size-8" />
</div>
```

### Alignment

Because the spinner component is an inline HTML element it can easily be aligned on the left, center, or right side using the `text-{left|center|right}` utility classes:

```twig {"preview":true}
<div class="w-full">
    <div class="text-left rtl:text-right">
        <div role="status">
            <twig:Spinner class="size-8 inline" />
            <span class="sr-only">Loading...</span>
        </div>
    </div>

    <div class="text-center">
        <div role="status">
            <twig:Spinner class="size-8 inline" />
            <span class="sr-only">Loading...</span>
        </div>
    </div>

    <div class="text-right rtl:text-left">
        <div role="status">
            <twig:Spinner class="size-8 inline" />
            <span class="sr-only">Loading...</span>
        </div>
    </div>
</div>
```

### Spinner with card

Use this animated loading indicator when content inside of a card is still loading.

```twig {"preview":true}
<twig:Card class="max-w-sm relative">
    <twig:Card:Header class="opacity-20">
        <twig:Card:Title as="h5">Noteworthy technology acquisitions 2021</twig:Card:Title>
    </twig:Card:Header>

    <twig:Card:Content class="opacity-20">
        <p>Here are the biggest technology acquisitions of 2025 so far, in reverse chronological order.</p>
    </twig:Card:Content>

    <div role="status" class="absolute -translate-x-1/2 -translate-y-1/2 top-2/4 left-1/2">
        <twig:Spinner class="size-8 text-brand"/>
        <span class="sr-only">Loading...</span>
    </div>
</twig:Card>
```

### Progress spinner

Use this animated spinner component inside a list of steppers elements.

```twig {"preview":true}
<div>
    <h2 class="mb-4 text-lg font-medium text-heading">Converting your image:</h2>
    <ul class="max-w-md space-y-3 text-body list-inside">
        <li class="flex items-center">
            <twig:ux:icon name="flowbite:check-circle-outline" class="size-5 text-fg-success shrink-0 me-2" />
            Upload your file to our website
        </li>
        <li class="flex items-center">
            <twig:ux:icon name="flowbite:check-circle-outline" class="size-5 text-fg-success shrink-0 me-2" />
            Choose your file format
        </li>
        <li class="flex items-center">
            <div role="status">
                <twig:Spinner class="size-4 text-brand me-2"/>
                <span class="sr-only">Loading...</span>
            </div>
            Preparing your file
        </li>
    </ul>
</div>
```

### Buttons

The spinner component can also be used inside elements such as buttons when submitting form data:

```twig {"preview":true}
<div>
    <twig:Button variant="tertiary">
        <twig:Spinner class="me-2 text-brand" />
        Loading...
    </twig:Button>
</div>
```

## API Reference

::: api-reference
