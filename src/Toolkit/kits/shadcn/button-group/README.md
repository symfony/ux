# Button Group

A container that groups related buttons together with consistent styling.

::: example Demo {"height": "200px"}

## Installation

::: installation

## Usage

::: example Usage

## Accessibility

- The `ButtonGroup` component has the `role` attribute set to `group`.
- Use `Tab` to navigate between the buttons in the group.
- Use `aria-label` or `aria-labelledby` to label the button group.

## Examples

### Orientation

Set the `orientation` prop to change the button group layout.

::: example Orientation {"height": "200px"}

### Size

Control the size of buttons using the `size` prop on individual buttons.

::: example Size {"height": "300px"}

### Nested

Nest `ButtonGroup` components to create button groups with spacing.

::: example Nested {"height": "200px"}

### Separator

The `ButtonGroup:Separator` component visually divides buttons within a group.

Buttons with variant `outline` do not need a separator since they have a border. For other variants, a separator is recommended to improve the visual hierarchy.

::: example Separator {"height": "200px"}

### Split

Create a split button group by adding two buttons separated by a `ButtonGroup:Separator`.

::: example Split {"height": "200px"}

### Input

Wrap an `Input` component with buttons.

::: example Input {"height": "200px"}

### Input Group

Wrap an `InputGroup` component to create complex input layouts.

::: example Input Group {"height": "200px"}

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

::: example RTL {"height": "320px"}

## API Reference

::: api-reference
