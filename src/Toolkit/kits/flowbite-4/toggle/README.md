# Toggle

Use the toggle component to switch between a binary state of true or false using a single click available in multiple sizes, variants, and colors

```twig {"preview":true}
<twig:Toggle id="checked" value="" checked>
    Toggle me
</twig:Toggle>
```

## Installation

::: installation

## Usage

```twig
<twig:Toggle>
    Label
</twig:Toggle>
```

## Examples

### Disabled

Apply the disabled attribute to disallow the users from making any further selections.

```twig {"preview":true}
<div class="flex flex-col gap-3">
    <twig:Toggle id="checked" value="" disabled>
        Toggle me
    </twig:Toggle>

    <twig:Toggle id="checked" value="" checked disabled>
        Toggle me
    </twig:Toggle>
</div>
```

## API Reference

::: api-reference
