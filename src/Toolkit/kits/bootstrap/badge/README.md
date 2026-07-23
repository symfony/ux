# Badge

A small count or label used to highlight status, counts, or short contextual information.

```twig {"preview":true,"height":"140px"}
<div class="d-flex flex-wrap align-items-center gap-2">
    <twig:Badge color="primary">New</twig:Badge>
    <twig:Badge color="success">Published</twig:Badge>
    <twig:Badge color="warning">Pending</twig:Badge>
    <twig:Badge color="danger" pill>99+</twig:Badge>
    <button type="button" class="btn btn-primary position-relative ms-2">
        Inbox
        <twig:Badge color="danger" pill class="position-absolute top-0 start-100 translate-middle">
            12
            <span class="visually-hidden">unread messages</span>
        </twig:Badge>
    </button>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Badge>New</twig:Badge>
```

## Accessibility

Do not rely on badge color alone to communicate meaning. Include meaningful visible text or additional context with Bootstrap's `visually-hidden` utility.

When a badge contains a count, make sure its relationship to the surrounding heading, button, or link is clear to assistive technologies.

## Examples

### Headings

Badges inherit their size from the immediate parent, so they scale naturally inside headings.

```twig {"preview":true,"height":"350px"}
<h1>Example heading <twig:Badge color="secondary">New</twig:Badge></h1>
<h2>Example heading <twig:Badge color="secondary">New</twig:Badge></h2>
<h3>Example heading <twig:Badge color="secondary">New</twig:Badge></h3>
<h4>Example heading <twig:Badge color="secondary">New</twig:Badge></h4>
<h5>Example heading <twig:Badge color="secondary">New</twig:Badge></h5>
<h6>Example heading <twig:Badge color="secondary">New</twig:Badge></h6>
```

### Buttons

Place a badge inside a button to display a related count.

```twig {"preview":true,"height":"140px"}
<button type="button" class="btn btn-primary">
    Notifications <twig:Badge color="secondary">4</twig:Badge>
</button>
```

### Positioned

Combine badges with Bootstrap's position utilities to create counters and status indicators.

```twig {"preview":true,"height":"140px"}
<div class="d-flex align-items-center gap-5">
    <button type="button" class="btn btn-primary position-relative">
        Inbox
        <twig:Badge color="danger" pill class="position-absolute top-0 start-100 translate-middle">
            99+
            <span class="visually-hidden">unread messages</span>
        </twig:Badge>
    </button>

    <button type="button" class="btn btn-primary position-relative">
        Profile
        <span class="position-absolute top-0 start-100 translate-middle p-2 bg-danger border border-light rounded-circle">
            <span class="visually-hidden">New alerts</span>
        </span>
    </button>
</div>
```

### Background colors

Use contextual background colors while keeping the badge's meaning clear from its text.

```twig {"preview":true,"height":"120px"}
<div class="d-flex flex-wrap gap-2">
    <twig:Badge color="primary">Primary</twig:Badge>
    <twig:Badge color="secondary">Secondary</twig:Badge>
    <twig:Badge color="success">Success</twig:Badge>
    <twig:Badge color="danger">Danger</twig:Badge>
    <twig:Badge color="warning">Warning</twig:Badge>
    <twig:Badge color="info">Info</twig:Badge>
    <twig:Badge color="light">Light</twig:Badge>
    <twig:Badge color="dark">Dark</twig:Badge>
</div>
```

### Pill badges

Use the pill style for a more rounded badge with a larger border radius.

```twig {"preview":true,"height":"120px"}
<div class="d-flex flex-wrap gap-2">
    <twig:Badge color="primary" pill>Primary</twig:Badge>
    <twig:Badge color="secondary" pill>Secondary</twig:Badge>
    <twig:Badge color="success" pill>Success</twig:Badge>
    <twig:Badge color="danger" pill>Danger</twig:Badge>
    <twig:Badge color="warning" pill>Warning</twig:Badge>
    <twig:Badge color="info" pill>Info</twig:Badge>
    <twig:Badge color="light" pill>Light</twig:Badge>
    <twig:Badge color="dark" pill>Dark</twig:Badge>
</div>
```

## API Reference

::: api-reference
