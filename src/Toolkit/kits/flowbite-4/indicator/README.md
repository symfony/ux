# Indicator

Use the indicator component to show a number count, account status, or as a loading label positioned relative to the parent component coded with Tailwind CSS

```twig {"preview":true}
<div class="flex justify-center gap-4">
    <twig:Indicator variant="gray" />
    <twig:Indicator variant="dark" />
    <twig:Indicator variant="brand" />
    <twig:Indicator variant="success" />
    <twig:Indicator variant="danger" />
    <twig:Indicator variant="purple" />
    <twig:Indicator variant="indigo" />
    <twig:Indicator variant="warning" />
    <twig:Indicator variant="teal" />
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Indicator
    variant="brand | gray | dark | danger | success | warning | purple | indigo | teal"
>
    Value
</twig:Indicator>
```

## Examples

### Count indicator

This example can be used to show a number count inside the indicator and position it relative to a button component.

```twig {"preview":true}
<twig:Button class="relative">
    <twig:ux:icon name="flowbite:inbox-full-outline" class="size-4 me-1.5 -ms-0.5" aria-hidden="true" />
    <span class="sr-only">Notifications</span>
    Messages
    <twig:Indicator variant="danger" size="lg" class="absolute -top-2 -end-2 border-2 border-buffer">8</twig:Indicator>
</twig:Button>
```

## API Reference

::: api-reference
