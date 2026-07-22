# Navs and Tabs

Build navigation components and accessible dynamic tabbed interfaces with Bootstrap.

```twig {"preview":true,"height":"180px"}
<div>
    <twig:Nav type="tabs" role="tablist" class="mb-3">
        <twig:Nav:Item presentation>
            <twig:Nav:Link tag="button" id="demo-home-tab" target="#demo-home" active>Home</twig:Nav:Link>
        </twig:Nav:Item>
        <twig:Nav:Item presentation>
            <twig:Nav:Link tag="button" id="demo-profile-tab" target="#demo-profile">Profile</twig:Nav:Link>
        </twig:Nav:Item>
        <twig:Nav:Item presentation>
            <twig:Nav:Link tag="button" id="demo-contact-tab" target="#demo-contact">Contact</twig:Nav:Link>
        </twig:Nav:Item>
    </twig:Nav>
    <twig:TabContent>
        <twig:TabPane id="demo-home" labelledBy="demo-home-tab" active fade>Home tab content.</twig:TabPane>
        <twig:TabPane id="demo-profile" labelledBy="demo-profile-tab" fade>Profile tab content.</twig:TabPane>
        <twig:TabPane id="demo-contact" labelledBy="demo-contact-tab" fade>Contact tab content.</twig:TabPane>
    </twig:TabContent>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Nav>
    <twig:Nav:Item>
        <twig:Nav:Link href="#" active>Active</twig:Nav:Link>
    </twig:Nav:Item>
    <twig:Nav:Item>
        <twig:Nav:Link href="#">Link</twig:Nav:Link>
    </twig:Nav:Item>
    <twig:Nav:Item>
        <twig:Nav:Link disabled>Disabled</twig:Nav:Link>
    </twig:Nav:Item>
</twig:Nav>
<twig:TabContent>
    <twig:TabPane id="demo-home" labelledBy="demo-home-tab" active fade>
        Home tab content.
    </twig:TabPane>
    <twig:TabPane id="demo-profile" labelledBy="demo-profile-tab" fade>
        Profile tab content.
    </twig:TabPane>
    <twig:TabPane id="demo-contact" labelledBy="demo-contact-tab" fade>
        Contact tab content.
    </twig:TabPane>
</twig:TabContent>
```

## Accessibility

Use `aria-current="page"` for active navigation links. Dynamic tab interfaces instead need `role="tablist"`, `role="tab"`, `role="tabpanel"`, and the `aria-selected`, `aria-controls`, and `aria-labelledby` relationships shown in the examples.

Prefer buttons for dynamic tabs. Do not put `role="tablist"` directly on a `nav` landmark; place it on a nested `div` instead. Vertical tab lists also need `aria-orientation="vertical"`.

## Examples

### Base nav

Build navigation with list markup or direct links inside a `nav` element.

```twig {"preview":true}
<div class="d-flex flex-column gap-3">
    <twig:Nav>
        <twig:Nav:Item><twig:Nav:Link href="#" active>Active</twig:Nav:Link></twig:Nav:Item>
        <twig:Nav:Item><twig:Nav:Link href="#">Link</twig:Nav:Link></twig:Nav:Item>
        <twig:Nav:Item><twig:Nav:Link href="#">Link</twig:Nav:Link></twig:Nav:Item>
        <twig:Nav:Item><twig:Nav:Link disabled>Disabled</twig:Nav:Link></twig:Nav:Item>
    </twig:Nav>

    <twig:Nav tag="nav" aria-label="Primary navigation">
        <twig:Nav:Link href="#" active>Active</twig:Nav:Link>
        <twig:Nav:Link href="#">Link</twig:Nav:Link>
        <twig:Nav:Link href="#">Link</twig:Nav:Link>
        <twig:Nav:Link disabled>Disabled</twig:Nav:Link>
    </twig:Nav>
</div>
```

### Horizontal alignment

Align navigation items with Bootstrap flexbox utilities through the `align` prop.

```twig {"preview":true}
<div class="d-flex flex-column gap-3">
    <twig:Nav align="center">
        <twig:Nav:Item><twig:Nav:Link href="#" active>Active</twig:Nav:Link></twig:Nav:Item>
        <twig:Nav:Item><twig:Nav:Link href="#">Link</twig:Nav:Link></twig:Nav:Item>
        <twig:Nav:Item><twig:Nav:Link href="#">Link</twig:Nav:Link></twig:Nav:Item>
        <twig:Nav:Item><twig:Nav:Link disabled>Disabled</twig:Nav:Link></twig:Nav:Item>
    </twig:Nav>

    <twig:Nav align="end">
        <twig:Nav:Item><twig:Nav:Link href="#" active>Active</twig:Nav:Link></twig:Nav:Item>
        <twig:Nav:Item><twig:Nav:Link href="#">Link</twig:Nav:Link></twig:Nav:Item>
        <twig:Nav:Item><twig:Nav:Link href="#">Link</twig:Nav:Link></twig:Nav:Item>
        <twig:Nav:Item><twig:Nav:Link disabled>Disabled</twig:Nav:Link></twig:Nav:Item>
    </twig:Nav>
</div>
```

### Vertical

Stack navigation links vertically with list or landmark markup.

```twig {"preview":true,"height":"260px"}
<div class="d-flex gap-5">
    <twig:Nav vertical>
        <twig:Nav:Item><twig:Nav:Link href="#" active>Active</twig:Nav:Link></twig:Nav:Item>
        <twig:Nav:Item><twig:Nav:Link href="#">Link</twig:Nav:Link></twig:Nav:Item>
        <twig:Nav:Item><twig:Nav:Link href="#">Link</twig:Nav:Link></twig:Nav:Item>
        <twig:Nav:Item><twig:Nav:Link disabled>Disabled</twig:Nav:Link></twig:Nav:Item>
    </twig:Nav>

    <twig:Nav tag="nav" vertical aria-label="Secondary navigation">
        <twig:Nav:Link href="#" active>Active</twig:Nav:Link>
        <twig:Nav:Link href="#">Link</twig:Nav:Link>
        <twig:Nav:Link href="#">Link</twig:Nav:Link>
        <twig:Nav:Link disabled>Disabled</twig:Nav:Link>
    </twig:Nav>
</div>
```

### Tabs

Apply the static tab appearance to navigation links.

```twig {"preview":true}
<twig:Nav type="tabs">
    <twig:Nav:Item><twig:Nav:Link href="#" active>Active</twig:Nav:Link></twig:Nav:Item>
    <twig:Nav:Item><twig:Nav:Link href="#">Link</twig:Nav:Link></twig:Nav:Item>
    <twig:Nav:Item><twig:Nav:Link href="#">Link</twig:Nav:Link></twig:Nav:Item>
    <twig:Nav:Item><twig:Nav:Link disabled>Disabled</twig:Nav:Link></twig:Nav:Item>
</twig:Nav>
```

### Pills

Render active navigation links with Bootstrap's pill style.

```twig {"preview":true}
<twig:Nav type="pills">
    <twig:Nav:Item><twig:Nav:Link href="#" active>Active</twig:Nav:Link></twig:Nav:Item>
    <twig:Nav:Item><twig:Nav:Link href="#">Link</twig:Nav:Link></twig:Nav:Item>
    <twig:Nav:Item><twig:Nav:Link href="#">Link</twig:Nav:Link></twig:Nav:Item>
    <twig:Nav:Item><twig:Nav:Link disabled>Disabled</twig:Nav:Link></twig:Nav:Item>
</twig:Nav>
```

### Underline

Use the underline style introduced in Bootstrap 5.3.

```twig {"preview":true}
<twig:Nav type="underline">
    <twig:Nav:Item><twig:Nav:Link href="#" active>Active</twig:Nav:Link></twig:Nav:Item>
    <twig:Nav:Item><twig:Nav:Link href="#">Link</twig:Nav:Link></twig:Nav:Item>
    <twig:Nav:Item><twig:Nav:Link href="#">Link</twig:Nav:Link></twig:Nav:Item>
    <twig:Nav:Item><twig:Nav:Link disabled>Disabled</twig:Nav:Link></twig:Nav:Item>
</twig:Nav>
```

### Fill and justify

Fill the available width proportionally or give every navigation item the same width.

```twig {"preview":true,"height":"220px"}
<div class="d-flex flex-column gap-3">
    <twig:Nav type="pills" fill>
        <twig:Nav:Item><twig:Nav:Link href="#" active>Active</twig:Nav:Link></twig:Nav:Item>
        <twig:Nav:Item><twig:Nav:Link href="#">Much longer nav link</twig:Nav:Link></twig:Nav:Item>
        <twig:Nav:Item><twig:Nav:Link href="#">Link</twig:Nav:Link></twig:Nav:Item>
        <twig:Nav:Item><twig:Nav:Link disabled>Disabled</twig:Nav:Link></twig:Nav:Item>
    </twig:Nav>

    <twig:Nav tag="nav" type="pills" justified aria-label="Equal width navigation">
        <twig:Nav:Link href="#" active>Active</twig:Nav:Link>
        <twig:Nav:Link href="#">Much longer nav link</twig:Nav:Link>
        <twig:Nav:Link href="#">Link</twig:Nav:Link>
        <twig:Nav:Link disabled>Disabled</twig:Nav:Link>
    </twig:Nav>
</div>
```

### Responsive flex

Combine the component with responsive flex utilities for breakpoint-specific layouts.

```twig {"preview":true}
<twig:Nav tag="nav" type="pills" class="flex-column flex-sm-row" aria-label="Responsive navigation">
    <twig:Nav:Link href="#" active class="flex-sm-fill text-sm-center">Active</twig:Nav:Link>
    <twig:Nav:Link href="#" class="flex-sm-fill text-sm-center">Longer nav link</twig:Nav:Link>
    <twig:Nav:Link href="#" class="flex-sm-fill text-sm-center">Link</twig:Nav:Link>
    <twig:Nav:Link disabled class="flex-sm-fill text-sm-center">Disabled</twig:Nav:Link>
</twig:Nav>
```

### Tabs with dropdowns

Add a Bootstrap dropdown to a static tabs navigation.

```twig {"preview":true,"height":"400px"}
<twig:Nav type="tabs">
    <twig:Nav:Item><twig:Nav:Link href="#" active>Active</twig:Nav:Link></twig:Nav:Item>
    <twig:Nav:Item class="dropdown">
        <twig:Nav:Link href="#" class="dropdown-toggle" data-bs-toggle="dropdown" role="button" aria-expanded="false">Dropdown</twig:Nav:Link>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="#">Action</a></li>
            <li><a class="dropdown-item" href="#">Another action</a></li>
            <li><a class="dropdown-item" href="#">Something else here</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#">Separated link</a></li>
        </ul>
    </twig:Nav:Item>
    <twig:Nav:Item><twig:Nav:Link href="#">Link</twig:Nav:Link></twig:Nav:Item>
    <twig:Nav:Item><twig:Nav:Link disabled>Disabled</twig:Nav:Link></twig:Nav:Item>
</twig:Nav>
```

### Pills with dropdowns

Add a Bootstrap dropdown to a static pills navigation.

```twig {"preview":true,"height":"400px"}
<twig:Nav type="pills">
    <twig:Nav:Item><twig:Nav:Link href="#" active>Active</twig:Nav:Link></twig:Nav:Item>
    <twig:Nav:Item class="dropdown">
        <twig:Nav:Link href="#" class="dropdown-toggle" data-bs-toggle="dropdown" role="button" aria-expanded="false">Dropdown</twig:Nav:Link>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="#">Action</a></li>
            <li><a class="dropdown-item" href="#">Another action</a></li>
            <li><a class="dropdown-item" href="#">Something else here</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#">Separated link</a></li>
        </ul>
    </twig:Nav:Item>
    <twig:Nav:Item><twig:Nav:Link href="#">Link</twig:Nav:Link></twig:Nav:Item>
    <twig:Nav:Item><twig:Nav:Link disabled>Disabled</twig:Nav:Link></twig:Nav:Item>
</twig:Nav>
```

### JavaScript tabs

Connect tab buttons to focusable content panels with Bootstrap's Tab plugin.

```twig {"preview":true}
<twig:Nav type="tabs" role="tablist" class="mb-3">
    <twig:Nav:Item presentation><twig:Nav:Link tag="button" id="home-tab" target="#home-tab-pane" active>Home</twig:Nav:Link></twig:Nav:Item>
    <twig:Nav:Item presentation><twig:Nav:Link tag="button" id="profile-tab" target="#profile-tab-pane">Profile</twig:Nav:Link></twig:Nav:Item>
    <twig:Nav:Item presentation><twig:Nav:Link tag="button" id="contact-tab" target="#contact-tab-pane">Contact</twig:Nav:Link></twig:Nav:Item>
    <twig:Nav:Item presentation><twig:Nav:Link tag="button" id="disabled-tab" target="#disabled-tab-pane" disabled>Disabled</twig:Nav:Link></twig:Nav:Item>
</twig:Nav>
<twig:TabContent>
    <twig:TabPane id="home-tab-pane" labelledBy="home-tab" active fade>This is the Home tab content.</twig:TabPane>
    <twig:TabPane id="profile-tab-pane" labelledBy="profile-tab" fade>This is the Profile tab content.</twig:TabPane>
    <twig:TabPane id="contact-tab-pane" labelledBy="contact-tab" fade>This is the Contact tab content.</twig:TabPane>
    <twig:TabPane id="disabled-tab-pane" labelledBy="disabled-tab" fade>This tab is disabled.</twig:TabPane>
</twig:TabContent>
```

### JavaScript nav

Keep the navigation landmark while placing `role="tablist"` on a nested container.

```twig {"preview":true}
<nav aria-label="Account sections">
    <twig:Nav tag="div" type="tabs" role="tablist" class="mb-3">
        <twig:Nav:Link tag="button" id="nav-home-tab" target="#nav-home" active>Home</twig:Nav:Link>
        <twig:Nav:Link tag="button" id="nav-profile-tab" target="#nav-profile">Profile</twig:Nav:Link>
        <twig:Nav:Link tag="button" id="nav-contact-tab" target="#nav-contact">Contact</twig:Nav:Link>
        <twig:Nav:Link tag="button" id="nav-disabled-tab" target="#nav-disabled" disabled>Disabled</twig:Nav:Link>
    </twig:Nav>
</nav>
<twig:TabContent>
    <twig:TabPane id="nav-home" labelledBy="nav-home-tab" active fade>This is the Home tab content.</twig:TabPane>
    <twig:TabPane id="nav-profile" labelledBy="nav-profile-tab" fade>This is the Profile tab content.</twig:TabPane>
    <twig:TabPane id="nav-contact" labelledBy="nav-contact-tab" fade>This is the Contact tab content.</twig:TabPane>
    <twig:TabPane id="nav-disabled" labelledBy="nav-disabled-tab" fade>This tab is disabled.</twig:TabPane>
</twig:TabContent>
```

### JavaScript pills

Use the same dynamic tab semantics with Bootstrap's pill appearance.

```twig {"preview":true}
<div>
    <twig:Nav type="pills" role="tablist" class="mb-3">
        <twig:Nav:Item presentation><twig:Nav:Link tag="button" id="pills-home-tab" target="#pills-home" toggle="pill" active>Home</twig:Nav:Link></twig:Nav:Item>
        <twig:Nav:Item presentation><twig:Nav:Link tag="button" id="pills-profile-tab" target="#pills-profile" toggle="pill">Profile</twig:Nav:Link></twig:Nav:Item>
        <twig:Nav:Item presentation><twig:Nav:Link tag="button" id="pills-contact-tab" target="#pills-contact" toggle="pill">Contact</twig:Nav:Link></twig:Nav:Item>
        <twig:Nav:Item presentation><twig:Nav:Link tag="button" id="pills-disabled-tab" target="#pills-disabled" toggle="pill" disabled>Disabled</twig:Nav:Link></twig:Nav:Item>
    </twig:Nav>
    <twig:TabContent>
        <twig:TabPane id="pills-home" labelledBy="pills-home-tab" active fade>This is the Home pill content.</twig:TabPane>
        <twig:TabPane id="pills-profile" labelledBy="pills-profile-tab" fade>This is the Profile pill content.</twig:TabPane>
        <twig:TabPane id="pills-contact" labelledBy="pills-contact-tab" fade>This is the Contact pill content.</twig:TabPane>
        <twig:TabPane id="pills-disabled" labelledBy="pills-disabled-tab" fade>This pill is disabled.</twig:TabPane>
    </twig:TabContent>
</div>
```

### Vertical pills

Build a vertical dynamic tab interface and expose its orientation to assistive technologies.

```twig {"preview":true,"height":"300px"}
<div class="d-flex align-items-start">
    <twig:Nav tag="div" type="pills" vertical role="tablist" class="me-3" aria-orientation="vertical">
        <twig:Nav:Link tag="button" id="vertical-home-tab" target="#vertical-home" toggle="pill" active>Home</twig:Nav:Link>
        <twig:Nav:Link tag="button" id="vertical-profile-tab" target="#vertical-profile" toggle="pill">Profile</twig:Nav:Link>
        <twig:Nav:Link tag="button" id="vertical-disabled-tab" target="#vertical-disabled" toggle="pill" disabled>Disabled</twig:Nav:Link>
        <twig:Nav:Link tag="button" id="vertical-messages-tab" target="#vertical-messages" toggle="pill">Messages</twig:Nav:Link>
        <twig:Nav:Link tag="button" id="vertical-settings-tab" target="#vertical-settings" toggle="pill">Settings</twig:Nav:Link>
    </twig:Nav>
    <twig:TabContent>
        <twig:TabPane id="vertical-home" labelledBy="vertical-home-tab" active fade>Home content.</twig:TabPane>
        <twig:TabPane id="vertical-profile" labelledBy="vertical-profile-tab" fade>Profile content.</twig:TabPane>
        <twig:TabPane id="vertical-disabled" labelledBy="vertical-disabled-tab" fade>Disabled content.</twig:TabPane>
        <twig:TabPane id="vertical-messages" labelledBy="vertical-messages-tab" fade>Messages content.</twig:TabPane>
        <twig:TabPane id="vertical-settings" labelledBy="vertical-settings-tab" fade>Settings content.</twig:TabPane>
    </twig:TabContent>
</div>
```

### Fade effect

Add Bootstrap's fade transition to tab panels. The initially active panel also receives `show`.

```twig {"preview":true}
<div class="w-75">
    <twig:Nav type="tabs" role="tablist" class="mb-3">
        <twig:Nav:Item presentation><twig:Nav:Link tag="button" id="fade-home-tab" target="#fade-home" active>Home</twig:Nav:Link></twig:Nav:Item>
        <twig:Nav:Item presentation><twig:Nav:Link tag="button" id="fade-profile-tab" target="#fade-profile">Profile</twig:Nav:Link></twig:Nav:Item>
    </twig:Nav>
    <twig:TabContent>
        <twig:TabPane id="fade-home" labelledBy="fade-home-tab" active fade>The active panel also receives the `show` class.</twig:TabPane>
        <twig:TabPane id="fade-profile" labelledBy="fade-profile-tab" fade>Inactive panels only receive the `fade` class.</twig:TabPane>
    </twig:TabContent>
</div>
```

## API Reference

::: api-reference
