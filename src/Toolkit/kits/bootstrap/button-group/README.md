# Button Group

Groups a series of buttons on a single line or in a vertical column.

```twig {"preview":true}
<div class="d-flex flex-column align-items-start gap-3">
    <twig:ButtonGroup label="Primary actions">
        <twig:Button>Left</twig:Button>
        <twig:Button>Middle</twig:Button>
        <twig:Button>Right</twig:Button>
    </twig:ButtonGroup>

    <twig:ButtonGroup label="Mixed actions">
        <twig:Button color="danger">Delete</twig:Button>
        <twig:Button color="warning">Archive</twig:Button>
        <twig:Button color="success">Publish</twig:Button>
    </twig:ButtonGroup>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:ButtonGroup label="Basic example">
    <twig:Button>Left</twig:Button>
    <twig:Button>Middle</twig:Button>
    <twig:Button>Right</twig:Button>
</twig:ButtonGroup>
```

## Accessibility

Button groups need `role="group"` and an explicit accessible name. The `label` prop supplies `aria-label`; set it to `null` only when providing an equivalent `aria-labelledby` attribute.

Use `role="toolbar"` and a meaningful label on toolbars that combine multiple button groups.

## Examples

### Basic example

Group buttons or links together as one related set of controls.

```twig {"preview":true}
<div class="d-flex flex-column align-items-start gap-3">
    <twig:ButtonGroup label="Basic example">
        <twig:Button>Left</twig:Button>
        <twig:Button>Middle</twig:Button>
        <twig:Button>Right</twig:Button>
    </twig:ButtonGroup>

    <twig:ButtonGroup label="Page navigation">
        <twig:Button tag="a" href="#" class="active" aria-current="page">Active link</twig:Button>
        <twig:Button tag="a" href="#">Link</twig:Button>
        <twig:Button tag="a" href="#">Link</twig:Button>
    </twig:ButtonGroup>
</div>
```

### Mixed styles

Mix contextual button styles within one group.

```twig {"preview":true}
<twig:ButtonGroup label="Basic mixed styles example">
    <twig:Button color="danger">Left</twig:Button>
    <twig:Button color="warning">Middle</twig:Button>
    <twig:Button color="success">Right</twig:Button>
</twig:ButtonGroup>
```

### Outlined styles

Use outline buttons for a group with less visual weight.

```twig {"preview":true}
<twig:ButtonGroup label="Basic outlined example">
    <twig:Button outline>Left</twig:Button>
    <twig:Button outline>Middle</twig:Button>
    <twig:Button outline>Right</twig:Button>
</twig:ButtonGroup>
```

### Checkbox and radio button groups

Combine Bootstrap toggle inputs and labels into seamless checkbox or radio groups.

```twig {"preview":true}
<div class="d-flex flex-column align-items-start gap-3">
    <twig:ButtonGroup label="Basic checkbox toggle button group">
        <input type="checkbox" class="btn-check" id="btncheck1" autocomplete="off">
        <label class="btn btn-outline-primary" for="btncheck1">Checkbox 1</label>
        <input type="checkbox" class="btn-check" id="btncheck2" autocomplete="off">
        <label class="btn btn-outline-primary" for="btncheck2">Checkbox 2</label>
        <input type="checkbox" class="btn-check" id="btncheck3" autocomplete="off">
        <label class="btn btn-outline-primary" for="btncheck3">Checkbox 3</label>
    </twig:ButtonGroup>

    <twig:ButtonGroup label="Basic radio toggle button group">
        <input type="radio" class="btn-check" name="btnradio" id="btnradio1" autocomplete="off" checked>
        <label class="btn btn-outline-primary" for="btnradio1">Radio 1</label>
        <input type="radio" class="btn-check" name="btnradio" id="btnradio2" autocomplete="off">
        <label class="btn btn-outline-primary" for="btnradio2">Radio 2</label>
        <input type="radio" class="btn-check" name="btnradio" id="btnradio3" autocomplete="off">
        <label class="btn btn-outline-primary" for="btnradio3">Radio 3</label>
    </twig:ButtonGroup>
</div>
```

### Button toolbar

Combine several button groups, and optionally input groups, inside a labeled toolbar.

```twig {"preview":true}
<div class="d-flex flex-column gap-3">
    <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">
        <twig:ButtonGroup label="First group" class="me-2">
            <twig:Button>1</twig:Button>
            <twig:Button>2</twig:Button>
            <twig:Button>3</twig:Button>
            <twig:Button>4</twig:Button>
        </twig:ButtonGroup>
        <twig:ButtonGroup label="Second group" class="me-2">
            <twig:Button color="secondary">5</twig:Button>
            <twig:Button color="secondary">6</twig:Button>
            <twig:Button color="secondary">7</twig:Button>
        </twig:ButtonGroup>
        <twig:ButtonGroup label="Third group">
            <twig:Button color="info">8</twig:Button>
        </twig:ButtonGroup>
    </div>

    <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with input group">
        <twig:ButtonGroup label="First group" class="me-2">
            <twig:Button color="secondary" outline>1</twig:Button>
            <twig:Button color="secondary" outline>2</twig:Button>
            <twig:Button color="secondary" outline>3</twig:Button>
            <twig:Button color="secondary" outline>4</twig:Button>
        </twig:ButtonGroup>
        <div class="input-group">
            <div class="input-group-text" id="btnGroupAddon">@</div>
            <input type="text" class="form-control" placeholder="Input group example" aria-label="Input group example" aria-describedby="btnGroupAddon">
        </div>
    </div>

    <div class="btn-toolbar justify-content-between" role="toolbar" aria-label="Toolbar with justified input group">
        <twig:ButtonGroup label="First group">
            <twig:Button color="secondary" outline>1</twig:Button>
            <twig:Button color="secondary" outline>2</twig:Button>
            <twig:Button color="secondary" outline>3</twig:Button>
            <twig:Button color="secondary" outline>4</twig:Button>
        </twig:ButtonGroup>
        <div class="input-group">
            <div class="input-group-text" id="btnGroupAddon2">@</div>
            <input type="text" class="form-control" placeholder="Input group example" aria-label="Input group example" aria-describedby="btnGroupAddon2">
        </div>
    </div>
</div>
```

### Sizing

Apply a size to the group instead of repeating it on every button.

```twig {"preview":true}
<div class="d-flex flex-column align-items-start gap-2">
    <twig:ButtonGroup size="lg" label="Large button group">
        <twig:Button outline>Left</twig:Button>
        <twig:Button outline>Middle</twig:Button>
        <twig:Button outline>Right</twig:Button>
    </twig:ButtonGroup>

    <twig:ButtonGroup label="Default button group">
        <twig:Button outline>Left</twig:Button>
        <twig:Button outline>Middle</twig:Button>
        <twig:Button outline>Right</twig:Button>
    </twig:ButtonGroup>

    <twig:ButtonGroup size="sm" label="Small button group">
        <twig:Button outline>Left</twig:Button>
        <twig:Button outline>Middle</twig:Button>
        <twig:Button outline>Right</twig:Button>
    </twig:ButtonGroup>
</div>
```

### Nesting

Nest a button group to place a dropdown alongside other buttons.

```twig {"preview":true}
<twig:ButtonGroup label="Button group with nested dropdown">
    <twig:Button>1</twig:Button>
    <twig:Button>2</twig:Button>
    <twig:ButtonGroup label="Dropdown menu">
        <twig:Button class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Dropdown</twig:Button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="#">Dropdown link</a></li>
            <li><a class="dropdown-item" href="#">Dropdown link</a></li>
        </ul>
    </twig:ButtonGroup>
</twig:ButtonGroup>
```

### Vertical variation

Stack buttons, dropdowns, or radio controls in a vertical group.

```twig {"preview":true}
<div class="d-flex flex-wrap align-items-start gap-4">
    <twig:ButtonGroup vertical label="Vertical button group">
        <twig:Button>Button</twig:Button>
        <twig:Button>Button</twig:Button>
        <twig:Button>Button</twig:Button>
        <twig:Button>Button</twig:Button>
    </twig:ButtonGroup>

    <twig:ButtonGroup vertical label="Vertical button group with dropdowns">
        <twig:ButtonGroup label="Dropdown menu">
            <twig:Button class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Dropdown</twig:Button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">Dropdown link</a></li>
                <li><a class="dropdown-item" href="#">Dropdown link</a></li>
            </ul>
        </twig:ButtonGroup>
        <twig:Button>Button</twig:Button>
        <twig:Button>Button</twig:Button>
        <twig:ButtonGroup label="Dropstart menu" class="dropstart">
            <twig:Button class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Dropdown</twig:Button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">Dropdown link</a></li>
                <li><a class="dropdown-item" href="#">Dropdown link</a></li>
            </ul>
        </twig:ButtonGroup>
        <twig:ButtonGroup label="Dropend menu" class="dropend">
            <twig:Button class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Dropdown</twig:Button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">Dropdown link</a></li>
                <li><a class="dropdown-item" href="#">Dropdown link</a></li>
            </ul>
        </twig:ButtonGroup>
        <twig:ButtonGroup label="Dropup menu" class="dropup">
            <twig:Button class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Dropdown</twig:Button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">Dropdown link</a></li>
                <li><a class="dropdown-item" href="#">Dropdown link</a></li>
            </ul>
        </twig:ButtonGroup>
    </twig:ButtonGroup>

    <twig:ButtonGroup vertical label="Vertical radio toggle button group">
        <input type="radio" class="btn-check" name="vbtn-radio" id="vbtn-radio1" autocomplete="off" checked>
        <label class="btn btn-outline-danger" for="vbtn-radio1">Radio 1</label>
        <input type="radio" class="btn-check" name="vbtn-radio" id="vbtn-radio2" autocomplete="off">
        <label class="btn btn-outline-danger" for="vbtn-radio2">Radio 2</label>
        <input type="radio" class="btn-check" name="vbtn-radio" id="vbtn-radio3" autocomplete="off">
        <label class="btn btn-outline-danger" for="vbtn-radio3">Radio 3</label>
    </twig:ButtonGroup>
</div>
```

## API Reference

::: api-reference
