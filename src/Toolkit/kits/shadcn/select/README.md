# Select

A dropdown control that allows users to choose from a list of options.

```twig {"preview":true}
<twig:Select class="max-w-xs">
    <option value="apple">Apple</option>
    <option value="banana">Banana</option>
    <option value="blueberry">Blueberry</option>
    <option value="grapes">Grapes</option>
    <option value="pineapple">Pineapple</option>
</twig:Select>
```

## Installation

::: installation

## Usage

```twig
<twig:Select>
    <option value="1">1</option>
    <option value="2">2</option>
    <option value="3">3</option>
</twig:Select>
```

## Accessibility

- `Select` renders a real `<select>`, so it keeps the platform's own picker, keyboard handling and type-ahead. This is the most accessible way to present a list of options.
- Give it a visible `Label` bound with `for` and `id`. The first option is not a label, and a placeholder option should be `disabled` so it cannot be chosen.
- Group related options in a real `<optgroup>`, whose `label` is announced when moving into the group.
- Set `aria-invalid="true"` for the invalid styling, and point `aria-describedby` at the message explaining what is wrong. Prefer the native `required` attribute over a client-side check alone.

## API Reference

::: api-reference
