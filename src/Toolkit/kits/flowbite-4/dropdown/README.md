# Dropdown

The dropdown component can be used to show a list of menu items when clicking on an element such as a button and hiding it when focusing outside of the triggering element.

::: example Demo

## Installation

::: installation

## Usage

::: example Usage

## Examples

### Dropdown hover

Use the `̀triggerType="{hover|click}"` prop options to set whether the dropdown should be shown when hovering or clicking on the trigger element (ie. button).

There’s a 300ms default delay when showing or hiding the dropdown due to UI/UX reasons and how it may affect the interaction with other components on the page. Generally, we recommend using the `click` method.
::: example Dropdown hover {"height": "400px"}

#### Delay duration

You can use the `delay={milliseconds}` prop options to set the delay on when to show or hide the dropdown menu when using hover. You may want to use this depending on how the users interact with your interface.
In this example we add 500 milliseconds instead of the default 300.
::: example Delay {"height": "400px"}

### Dropdown divider

You can use multiple `Dropdown:Group`, the `Dropdown:Content` add a divider between the groups.
::: example Dropdown divider {"height": "600px"}

### With header

Use this example to show extra information outside of the list of menu items inside the dropdown.
::: example With Header {"height": "600px"}

### Multi-level dropdown

Use this example to enable multi-level dropdown menus by adding stacked elements inside of each other.
::: example Multilevel {"height": "600px"}

### With icon

Use the menu icon trigger element on components such as cards as an alternative element to the button.
::: example With Icon {"height": "400px"}

### Placement

You can also use the `placement={top|right|bottom|left}` prop options to choose the placement of the dropdown menu.
By default the positioning is set to the bottom side of the button.
::: example Placement {"height": "400px"}

## API Reference

::: api-reference
