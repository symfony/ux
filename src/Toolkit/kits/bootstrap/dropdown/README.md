# Dropdown

Display contextual menus from buttons and links in any Bootstrap direction.

```twig {"preview":true,"height":"220px"}
<div class="d-flex justify-content-center align-items-start align-self-stretch w-100">
    <twig:Dropdown>
        <twig:Dropdown:Toggle id="demo-dropdown">Dropdown button</twig:Dropdown:Toggle>
        <twig:Dropdown:Menu labelledBy="demo-dropdown">
            <twig:Dropdown:Item href="#">Action</twig:Dropdown:Item>
            <twig:Dropdown:Item href="#">Another action</twig:Dropdown:Item>
            <twig:Dropdown:Item href="#">Something else here</twig:Dropdown:Item>
        </twig:Dropdown:Menu>
    </twig:Dropdown>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Dropdown>
    <twig:Dropdown:Toggle id="actions-dropdown">Actions</twig:Dropdown:Toggle>
    <twig:Dropdown:Menu labelledBy="actions-dropdown">
        <twig:Dropdown:Item href="/edit">Edit</twig:Dropdown:Item>
        <twig:Dropdown:Item href="/duplicate">Duplicate</twig:Dropdown:Item>
        <twig:Dropdown:Divider />
        <twig:Dropdown:Item href="/archive">Archive</twig:Dropdown:Item>
    </twig:Dropdown:Menu>
</twig:Dropdown>
```

## Accessibility

Bootstrap dropdowns are generic popovers, so the component does not add ARIA menu roles automatically. Add `role="menu"`, `role="menuitem"`, and the matching keyboard behavior only when the dropdown implements the complete ARIA menu pattern.

Use a button for actions and reserve link toggles and items for navigation. Give a toggle an `id` and pass it to the menu's `labelledBy` prop when an explicit accessible relationship is useful. Disabled links receive `aria-disabled="true"` and are removed from sequential keyboard navigation, but application code must still prevent any custom activation behavior.

## Examples

### Single button

Use buttons or links with Bootstrap's contextual colors.

```twig {"preview":true,"height":"300px"}
<div class="d-flex flex-wrap justify-content-center align-items-start gap-2 w-100">
    {% for color in ['primary', 'secondary', 'success', 'info', 'warning', 'danger'] %}
        <twig:Dropdown>
            <twig:Dropdown:Toggle color="{{ color }}">{{ color|title }}</twig:Dropdown:Toggle>
            <twig:Dropdown:Menu>
                <twig:Dropdown:Item href="#">Action</twig:Dropdown:Item>
                <twig:Dropdown:Item href="#">Another action</twig:Dropdown:Item>
                <twig:Dropdown:Item href="#">Something else here</twig:Dropdown:Item>
            </twig:Dropdown:Menu>
        </twig:Dropdown>
    {% endfor %}
    <twig:Dropdown>
        <twig:Dropdown:Toggle tag="a" color="primary">Dropdown link</twig:Dropdown:Toggle>
        <twig:Dropdown:Menu>
            <twig:Dropdown:Item href="#">Action</twig:Dropdown:Item>
            <twig:Dropdown:Item href="#">Another action</twig:Dropdown:Item>
        </twig:Dropdown:Menu>
    </twig:Dropdown>
</div>
```

### Split button

Separate the primary action from the menu toggle.

```twig {"preview":true,"height":"320px"}
<div class="d-flex flex-wrap justify-content-center align-items-start gap-2 w-100">
    {% for color in ['primary', 'secondary', 'success', 'info', 'warning', 'danger'] %}
        <twig:Dropdown grouped>
            <button type="button" class="btn btn-{{ color }}">{{ color|title }}</button>
            <twig:Dropdown:Toggle color="{{ color }}" split />
            <twig:Dropdown:Menu>
                <twig:Dropdown:Item href="#">Action</twig:Dropdown:Item>
                <twig:Dropdown:Item href="#">Another action</twig:Dropdown:Item>
                <twig:Dropdown:Divider />
                <twig:Dropdown:Item href="#">Separated link</twig:Dropdown:Item>
            </twig:Dropdown:Menu>
        </twig:Dropdown>
    {% endfor %}
</div>
```

### Sizing

Create large and small regular or split dropdown buttons.

```twig {"preview":true,"height":"420px"}
<div class="d-flex flex-wrap justify-content-center align-items-center gap-3 w-100">
    <twig:Dropdown>
        <twig:Dropdown:Toggle color="secondary" size="lg">Large button</twig:Dropdown:Toggle>
        <twig:Dropdown:Menu><twig:Dropdown:Item href="#">Action</twig:Dropdown:Item></twig:Dropdown:Menu>
    </twig:Dropdown>
    <twig:Dropdown grouped>
        <button class="btn btn-light btn-lg" type="button">Large split button</button>
        <twig:Dropdown:Toggle color="light" size="lg" split />
        <twig:Dropdown:Menu><twig:Dropdown:Item href="#">Action</twig:Dropdown:Item></twig:Dropdown:Menu>
    </twig:Dropdown>
    <twig:Dropdown>
        <twig:Dropdown:Toggle color="secondary" size="sm">Small button</twig:Dropdown:Toggle>
        <twig:Dropdown:Menu><twig:Dropdown:Item href="#">Action</twig:Dropdown:Item></twig:Dropdown:Menu>
    </twig:Dropdown>
    <twig:Dropdown grouped>
        <button class="btn btn-light btn-sm" type="button">Small split button</button>
        <twig:Dropdown:Toggle color="light" size="sm" split />
        <twig:Dropdown:Menu><twig:Dropdown:Item href="#">Action</twig:Dropdown:Item></twig:Dropdown:Menu>
    </twig:Dropdown>
</div>
```

### Dark dropdowns

Render a dark menu with a visible active item.

```twig {"preview":true,"height":"260px"}
<div class="d-flex justify-content-center align-items-start align-self-stretch w-100">
    <twig:Dropdown>
        <twig:Dropdown:Toggle id="dark-dropdown" color="secondary">Dropdown button</twig:Dropdown:Toggle>
        <twig:Dropdown:Menu dark labelledBy="dark-dropdown">
            <twig:Dropdown:Item href="#" active>Action</twig:Dropdown:Item>
            <twig:Dropdown:Item href="#">Another action</twig:Dropdown:Item>
            <twig:Dropdown:Item href="#">Something else here</twig:Dropdown:Item>
            <twig:Dropdown:Divider />
            <twig:Dropdown:Item href="#">Separated link</twig:Dropdown:Item>
        </twig:Dropdown:Menu>
    </twig:Dropdown>
</div>
```

### Directions

Open menus from the center, above, or from either side.

```twig {"preview":true,"height":"360px"}
<div class="d-flex flex-wrap justify-content-center align-items-center gap-3 w-100">
    <twig:Dropdown direction="center">
        <twig:Dropdown:Toggle>Centered dropdown</twig:Dropdown:Toggle>
        <twig:Dropdown:Menu><twig:Dropdown:Item href="#">Action</twig:Dropdown:Item></twig:Dropdown:Menu>
    </twig:Dropdown>
    <twig:Dropdown direction="up">
        <twig:Dropdown:Toggle>Dropup</twig:Dropdown:Toggle>
        <twig:Dropdown:Menu><twig:Dropdown:Item href="#">Action</twig:Dropdown:Item></twig:Dropdown:Menu>
    </twig:Dropdown>
    <twig:Dropdown direction="up" grouped>
        <button type="button" class="btn btn-secondary">Split dropup</button>
        <twig:Dropdown:Toggle split />
        <twig:Dropdown:Menu><twig:Dropdown:Item href="#">Action</twig:Dropdown:Item></twig:Dropdown:Menu>
    </twig:Dropdown>
    <twig:Dropdown direction="up-center">
        <twig:Dropdown:Toggle>Centered dropup</twig:Dropdown:Toggle>
        <twig:Dropdown:Menu><twig:Dropdown:Item href="#">Action</twig:Dropdown:Item></twig:Dropdown:Menu>
    </twig:Dropdown>
    <twig:Dropdown direction="end">
        <twig:Dropdown:Toggle>Dropend</twig:Dropdown:Toggle>
        <twig:Dropdown:Menu><twig:Dropdown:Item href="#">Action</twig:Dropdown:Item></twig:Dropdown:Menu>
    </twig:Dropdown>
    <twig:Dropdown direction="end" grouped>
        <button type="button" class="btn btn-secondary">Split dropend</button>
        <twig:Dropdown:Toggle split />
        <twig:Dropdown:Menu><twig:Dropdown:Item href="#">Action</twig:Dropdown:Item></twig:Dropdown:Menu>
    </twig:Dropdown>
    <twig:Dropdown direction="start">
        <twig:Dropdown:Toggle>Dropstart</twig:Dropdown:Toggle>
        <twig:Dropdown:Menu><twig:Dropdown:Item href="#">Action</twig:Dropdown:Item></twig:Dropdown:Menu>
    </twig:Dropdown>
    <twig:Dropdown direction="start" grouped>
        <twig:Dropdown:Toggle split />
        <button type="button" class="btn btn-secondary">Split dropstart</button>
        <twig:Dropdown:Menu><twig:Dropdown:Item href="#">Action</twig:Dropdown:Item></twig:Dropdown:Menu>
    </twig:Dropdown>
</div>
```

### Menu items

Use links, buttons, or non-interactive text as menu content.

```twig {"preview":true,"height":"250px"}
<div class="d-flex flex-wrap justify-content-center align-items-start gap-4 w-100">
    <twig:Dropdown:Menu class="d-block position-static">
        <twig:Dropdown:Item href="#">Action</twig:Dropdown:Item>
        <twig:Dropdown:Item href="#">Another action</twig:Dropdown:Item>
        <twig:Dropdown:Item href="#">Something else here</twig:Dropdown:Item>
    </twig:Dropdown:Menu>
    <twig:Dropdown:Menu class="d-block position-static">
        <twig:Dropdown:Item tag="button">Action</twig:Dropdown:Item>
        <twig:Dropdown:Item tag="button">Another action</twig:Dropdown:Item>
        <twig:Dropdown:Item tag="button">Something else here</twig:Dropdown:Item>
    </twig:Dropdown:Menu>
    <twig:Dropdown:Menu class="d-block position-static">
        <twig:Dropdown:Text>Dropdown item text</twig:Dropdown:Text>
        <twig:Dropdown:Item href="#">Action</twig:Dropdown:Item>
    </twig:Dropdown:Menu>
</div>
```

### Active and disabled items

Communicate the current item and unavailable choices.

```twig {"preview":true,"height":"220px"}
<twig:Dropdown:Menu class="d-block position-static">
    <twig:Dropdown:Item href="#">Regular link</twig:Dropdown:Item>
    <twig:Dropdown:Item href="#" active>Active link</twig:Dropdown:Item>
    <twig:Dropdown:Item href="#" disabled>Disabled link</twig:Dropdown:Item>
</twig:Dropdown:Menu>
```

### Menu alignment

Align a menu against the end of its toggle.

```twig {"preview":true,"height":"220px"}
<div class="d-flex justify-content-center align-items-start align-self-stretch w-100">
    <twig:Dropdown>
        <twig:Dropdown:Toggle id="aligned-dropdown">Right-aligned menu</twig:Dropdown:Toggle>
        <twig:Dropdown:Menu align="end" labelledBy="aligned-dropdown">
            <twig:Dropdown:Item href="#">Action</twig:Dropdown:Item>
            <twig:Dropdown:Item href="#">Another action</twig:Dropdown:Item>
            <twig:Dropdown:Item href="#">Something else here</twig:Dropdown:Item>
        </twig:Dropdown:Menu>
    </twig:Dropdown>
</div>
```

### Responsive alignment

Change menu alignment at Bootstrap breakpoints.

```twig {"preview":true,"height":"240px"}
<div class="d-flex flex-wrap justify-content-center align-items-start gap-3 w-100">
    <twig:Dropdown>
        <twig:Dropdown:Toggle data-bs-display="static">Left-aligned but right-aligned when large screen</twig:Dropdown:Toggle>
        <twig:Dropdown:Menu align="lg-end"><twig:Dropdown:Item href="#">Action</twig:Dropdown:Item></twig:Dropdown:Menu>
    </twig:Dropdown>
    <twig:Dropdown>
        <twig:Dropdown:Toggle data-bs-display="static">Right-aligned but left-aligned when large screen</twig:Dropdown:Toggle>
        <twig:Dropdown:Menu :align="['end', 'lg-start']"><twig:Dropdown:Item href="#">Action</twig:Dropdown:Item></twig:Dropdown:Menu>
    </twig:Dropdown>
</div>
```

### Alignment options

Combine responsive alignment with dropdowns that open in different directions.

```twig {"preview":true,"height":"520px"}
<div class="d-flex flex-wrap justify-content-center align-items-center gap-3 w-100">
    <twig:Dropdown>
        <twig:Dropdown:Toggle>Dropdown</twig:Dropdown:Toggle>
        <twig:Dropdown:Menu>
            <twig:Dropdown:Item href="#">Menu item</twig:Dropdown:Item>
            <twig:Dropdown:Item href="#">Menu item</twig:Dropdown:Item>
        </twig:Dropdown:Menu>
    </twig:Dropdown>
    <twig:Dropdown direction="end">
        <twig:Dropdown:Toggle>Dropend</twig:Dropdown:Toggle>
        <twig:Dropdown:Menu :align="['end', 'lg-start']">
            <twig:Dropdown:Item href="#">Right-aligned, left-aligned when large</twig:Dropdown:Item>
        </twig:Dropdown:Menu>
    </twig:Dropdown>
    <twig:Dropdown direction="start">
        <twig:Dropdown:Toggle>Dropstart</twig:Dropdown:Toggle>
        <twig:Dropdown:Menu align="lg-end">
            <twig:Dropdown:Item href="#">Left-aligned, right-aligned when large</twig:Dropdown:Item>
        </twig:Dropdown:Menu>
    </twig:Dropdown>
    <twig:Dropdown direction="up">
        <twig:Dropdown:Toggle>Dropup</twig:Dropdown:Toggle>
        <twig:Dropdown:Menu align="end"><twig:Dropdown:Item href="#">Right-aligned menu</twig:Dropdown:Item></twig:Dropdown:Menu>
    </twig:Dropdown>
</div>
```

### Headers, dividers, and text

Structure longer menus with headings, separators, and explanatory copy.

```twig {"preview":true,"height":"300px"}
<twig:Dropdown:Menu class="d-block position-static">
    <twig:Dropdown:Header>Dropdown header</twig:Dropdown:Header>
    <twig:Dropdown:Item href="#">Action</twig:Dropdown:Item>
    <twig:Dropdown:Item href="#">Another action</twig:Dropdown:Item>
    <twig:Dropdown:Divider />
    <twig:Dropdown:Text>Some example text that's free-flowing within the dropdown menu.</twig:Dropdown:Text>
    <twig:Dropdown:Item href="#">Separated link</twig:Dropdown:Item>
</twig:Dropdown:Menu>
```

### Forms

Display a static form or open one from a dropdown toggle.

```twig {"preview":true,"height":"400px"}
<div class="d-flex flex-wrap justify-content-center align-items-start gap-4 w-100">
    <twig:Dropdown:Menu tag="form" class="d-block position-static p-4">
        <div class="mb-3">
            <label for="dropdown-email" class="form-label">Email address</label>
            <input type="email" class="form-control" id="dropdown-email" placeholder="email@example.com">
        </div>
        <div class="mb-3">
            <label for="dropdown-password" class="form-label">Password</label>
            <input type="password" class="form-control" id="dropdown-password" placeholder="Password">
        </div>
        <div class="mb-3">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="dropdown-check">
                <label class="form-check-label" for="dropdown-check">Remember me</label>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Sign in</button>
    </twig:Dropdown:Menu>
    <twig:Dropdown>
        <twig:Dropdown:Toggle>Dropdown form</twig:Dropdown:Toggle>
        <twig:Dropdown:Menu tag="form" class="p-4">
            <div class="mb-3">
                <label for="menu-email" class="form-label">Email address</label>
                <input type="email" class="form-control" id="menu-email">
            </div>
            <button type="submit" class="btn btn-primary">Sign in</button>
        </twig:Dropdown:Menu>
    </twig:Dropdown>
</div>
```

### Dropdown options

Configure Popper's offset and reference element with Bootstrap data attributes.

```twig {"preview":true,"height":"230px"}
<div class="d-flex flex-wrap justify-content-center align-items-start gap-3 w-100">
    <twig:Dropdown>
        <twig:Dropdown:Toggle data-bs-offset="10,20">Offset</twig:Dropdown:Toggle>
        <twig:Dropdown:Menu><twig:Dropdown:Item href="#">Action</twig:Dropdown:Item></twig:Dropdown:Menu>
    </twig:Dropdown>
    <twig:Dropdown grouped>
        <button type="button" class="btn btn-secondary">Reference</button>
        <twig:Dropdown:Toggle split data-bs-reference="parent" />
        <twig:Dropdown:Menu><twig:Dropdown:Item href="#">Action</twig:Dropdown:Item></twig:Dropdown:Menu>
    </twig:Dropdown>
</div>
```

### Auto close behavior

Choose which inside or outside interactions dismiss the menu.

```twig {"preview":true,"height":"280px"}
<div class="d-flex flex-wrap justify-content-center align-items-start gap-3 w-100">
    {% for behavior, label in {
    true: 'Default dropdown',
    inside: 'Clickable inside',
    outside: 'Clickable outside',
    false: 'Manual close',
    } %}
        <twig:Dropdown>
            <twig:Dropdown:Toggle data-bs-auto-close="{{ behavior }}">{{ label }}</twig:Dropdown:Toggle>
            <twig:Dropdown:Menu>
                <twig:Dropdown:Item href="#">Menu item</twig:Dropdown:Item>
                <twig:Dropdown:Item href="#">Menu item</twig:Dropdown:Item>
            </twig:Dropdown:Menu>
        </twig:Dropdown>
    {% endfor %}
</div>
```

## API Reference

::: api-reference
