# Scrollspy

Update Bootstrap navigation links as a scrollable region moves between sections.

```twig {"preview":true}
<div class="w-100">
    <nav id="demo-scrollspy-nav" class="navbar bg-body-tertiary px-3 mb-3 rounded">
        <span class="navbar-brand">Scrollspy</span>
        <ul class="nav nav-pills">
            <li class="nav-item"><a class="nav-link" href="#demo-item-1">First</a></li>
            <li class="nav-item"><a class="nav-link" href="#demo-item-2">Second</a></li>
            <li class="nav-item"><a class="nav-link" href="#demo-item-3">Third</a></li>
        </ul>
    </nav>

    <twig:Scrollspy target="#demo-scrollspy-nav" height="260px" class="border rounded p-3">
        {% for number in 1..3 %}
            <h4 id="demo-item-{{ number }}">Section {{ number }}</h4>
            <p>Scroll this region to update the active navigation item.</p>
            <p class="mb-5">The observed content stays inside its own scrollable container.</p>
        {% endfor %}
    </twig:Scrollspy>
</div>
```

## Installation

::: installation

## Usage

```twig
<div>
    <nav id="usage-scrollspy-nav" class="nav nav-pills mb-3">
        <a class="nav-link" href="#usage-first">First</a>
        <a class="nav-link" href="#usage-second">Second</a>
    </nav>

    <twig:Scrollspy target="#usage-scrollspy-nav" class="border rounded p-3">
        <h4 id="usage-first">First section</h4>
        <p>Scrollspy activates the navigation link for the section currently in view.</p>
        <div style="min-height: 150px"></div>
        <h4 id="usage-second">Second section</h4>
        <p>Every link must point to a matching section identifier.</p>
        <div style="min-height: 150px"></div>
    </twig:Scrollspy>
</div>
```

The `target` prop must select the navigation element, and every navigation link must point to the ID of a section inside the scrollable component. Bootstrap initializes Scrollspy through its data API.

## Accessibility

The component adds `tabindex="0"` so a scrollable region without focusable children remains keyboard-accessible. Use meaningful link labels, unique section IDs, and a logical heading hierarchy inside the observed content.

An active navigation style is a visual enhancement, not a substitute for headings or document structure. When smooth scrolling is enabled, Bootstrap respects `prefers-reduced-motion` through CSS.

## Examples

### Navbar

Track sections from links inside a Bootstrap navbar.

```twig {"preview":true}
<div class="w-100">
    <nav id="navbar-scrollspy" class="navbar bg-body-tertiary px-3 mb-3 rounded">
        <span class="navbar-brand">Navbar</span>
        <ul class="nav nav-pills">
            <li class="nav-item"><a class="nav-link" href="#navbar-first">First</a></li>
            <li class="nav-item"><a class="nav-link" href="#navbar-second">Second</a></li>
            <li class="nav-item"><a class="nav-link" href="#navbar-third">Third</a></li>
        </ul>
    </nav>

    <twig:Scrollspy target="#navbar-scrollspy" class="border rounded p-3">
        {% for id, title in {first: 'First heading', second: 'Second heading', third: 'Third heading'} %}
            <h4 id="navbar-{{ id }}">{{ title }}</h4>
            <p>Representative content for {{ title|lower }}. Keep scrolling to activate the next link.</p>
            <div style="min-height: 130px"></div>
        {% endfor %}
    </twig:Scrollspy>
</div>
```

### Nested nav

Activate both parent and nested navigation links for hierarchical content.

```twig {"preview":true}
<div class="row">
    <div class="col-4">
        <nav id="nested-scrollspy" class="h-100 flex-column align-items-stretch pe-3 border-end">
            <nav class="nav nav-pills flex-column">
                <a class="nav-link" href="#nested-item-1">Item 1</a>
                <nav class="nav nav-pills flex-column ms-3">
                    <a class="nav-link my-1" href="#nested-item-1-1">Item 1-1</a>
                    <a class="nav-link my-1" href="#nested-item-1-2">Item 1-2</a>
                </nav>
                <a class="nav-link" href="#nested-item-2">Item 2</a>
            </nav>
        </nav>
    </div>
    <div class="col-8">
        <twig:Scrollspy target="#nested-scrollspy" height="260px" smoothScroll class="p-2">
            {% for id in ['1', '1-1', '1-2', '2'] %}
                <h4 id="nested-item-{{ id }}">Item {{ id }}</h4>
                <p>Nested navigation activates parent and child links as their sections enter view.</p>
                <div style="min-height: 120px"></div>
            {% endfor %}
        </twig:Scrollspy>
    </div>
</div>
```

### List group

Use a list group as the navigation target.

```twig {"preview":true}
<div class="row">
    <div class="col-4">
        <div id="list-scrollspy" class="list-group">
            <a class="list-group-item list-group-item-action" href="#list-item-1">Item 1</a>
            <a class="list-group-item list-group-item-action" href="#list-item-2">Item 2</a>
            <a class="list-group-item list-group-item-action" href="#list-item-3">Item 3</a>
        </div>
    </div>
    <div class="col-8">
        <twig:Scrollspy target="#list-scrollspy" height="240px" class="p-2">
            {% for number in 1..3 %}
                <h4 id="list-item-{{ number }}">Item {{ number }}</h4>
                <p>List group links work as Scrollspy navigation controls.</p>
                <div style="min-height: 125px"></div>
            {% endfor %}
        </twig:Scrollspy>
    </div>
</div>
```

### Simple anchors

Track sections with lightweight anchor navigation and optional smooth scrolling.

```twig {"preview":true}
<div class="w-100">
    <nav id="simple-scrollspy" class="hstack gap-3 mb-3">
        <a class="p-2 link-offset-2 link-underline link-underline-opacity-0" href="#simple-first">First</a>
        <a class="p-2 link-offset-2 link-underline link-underline-opacity-0" href="#simple-second">Second</a>
        <a class="p-2 link-offset-2 link-underline link-underline-opacity-0" href="#simple-third">Third</a>
    </nav>

    <twig:Scrollspy target="#simple-scrollspy" smoothScroll height="240px" class="border rounded p-3">
        {% for id, title in {first: 'First section', second: 'Second section', third: 'Third section'} %}
            <h4 id="simple-{{ id }}">{{ title }}</h4>
            <p>Simple links receive the active class when this section becomes current.</p>
            <div style="min-height: 130px"></div>
        {% endfor %}
    </twig:Scrollspy>
</div>
```

## API Reference

::: api-reference
