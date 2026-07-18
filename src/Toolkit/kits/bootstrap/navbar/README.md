# Navbar

A powerful, responsive navigation header with support for branding, navigation, forms, and collapsing content.

```twig {"preview":true,"height":"260px"}
<twig:Navbar expand="lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Navbar</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarDemo" aria-controls="navbarDemo" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarDemo">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Link</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Dropdown</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">Action</a></li>
                        <li><a class="dropdown-item" href="#">Another action</a></li>
                    </ul>
                </li>
            </ul>
            <form class="d-flex" role="search">
                <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search">
                <button class="btn btn-outline-success" type="submit">Search</button>
            </form>
        </div>
    </div>
</twig:Navbar>
```

## Installation

::: installation

## Usage

```twig
<twig:Navbar>
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Navbar</a>
    </div>
</twig:Navbar>
```

## Accessibility

The component renders a native `nav` landmark. When a page contains multiple navigation landmarks, give each one a distinct accessible name with the `label` prop or an `aria-labelledby` attribute.

Mark the active link with `aria-current="page"`. Every toggler must expose `aria-controls`, `aria-expanded`, and a meaningful accessible label. When external content opens before its toggler in the document, move focus programmatically when needed.

## Examples

### Supported content

Compose branding, navigation, dropdowns, forms, togglers, and collapsing content inside one responsive navbar.

```twig {"preview":true,"height":"300px"}
<twig:Navbar expand="lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Navbar</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Link</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Dropdown</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">Action</a></li>
                        <li><a class="dropdown-item" href="#">Another action</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#">Something else here</a></li>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link disabled" aria-disabled="true">Disabled</a></li>
            </ul>
            <form class="d-flex" role="search">
                <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search">
                <button class="btn btn-outline-success" type="submit">Search</button>
            </form>
        </div>
    </div>
</twig:Navbar>
```

### Brand

Use text, headings, images, or image-and-text combinations for the navbar brand.

```twig {"preview":true,"height":"430px"}
<div class="d-flex flex-column gap-3">
    <twig:Navbar>
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Navbar</a>
        </div>
    </twig:Navbar>

    <twig:Navbar>
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">Navbar</span>
        </div>
    </twig:Navbar>

    <twig:Navbar>
        <div class="container">
            <a class="navbar-brand" href="#" aria-label="Bootstrap">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 118 94" width="30" height="24" aria-hidden="true">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M24.509 0c-6.733 0-11.715 5.893-11.492 12.284.214 6.14-.064 14.092-2.066 20.577C8.943 39.365 5.547 43.485 0 44.014v5.972c5.547.529 8.943 4.649 10.951 11.153 2.002 6.485 2.28 14.437 2.066 20.577C12.794 88.106 17.776 94 24.51 94H93.5c6.733 0 11.714-5.893 11.491-12.284-.214-6.14.064-14.092 2.066-20.577 2.009-6.504 5.396-10.624 10.943-11.153v-5.972c-5.547-.529-8.934-4.649-10.943-11.153-2.002-6.484-2.28-14.437-2.066-20.577C105.214 5.894 100.233 0 93.5 0H24.508zM80 57.863C80 66.663 73.436 72 62.543 72H44a2 2 0 01-2-2V24a2 2 0 012-2h18.437c9.083 0 15.044 4.92 15.044 12.474 0 5.302-4.01 10.049-9.119 10.88v.277C75.317 46.394 80 51.21 80 57.863zM60.521 28.34H49.948v14.934h8.905c6.884 0 10.68-2.772 10.68-7.727 0-4.643-3.264-7.207-9.012-7.207zM49.948 49.2v16.458H60.91c7.167 0 10.964-2.876 10.964-8.281 0-5.406-3.903-8.178-11.425-8.178H49.948z" fill="currentColor" />
                </svg>
            </a>
        </div>
    </twig:Navbar>

    <twig:Navbar>
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 118 94" width="30" height="24" class="d-inline-block align-text-top" aria-hidden="true">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M24.509 0c-6.733 0-11.715 5.893-11.492 12.284.214 6.14-.064 14.092-2.066 20.577C8.943 39.365 5.547 43.485 0 44.014v5.972c5.547.529 8.943 4.649 10.951 11.153 2.002 6.485 2.28 14.437 2.066 20.577C12.794 88.106 17.776 94 24.51 94H93.5c6.733 0 11.714-5.893 11.491-12.284-.214-6.14.064-14.092 2.066-20.577 2.009-6.504 5.396-10.624 10.943-11.153v-5.972c-5.547-.529-8.934-4.649-10.943-11.153-2.002-6.484-2.28-14.437-2.066-20.577C105.214 5.894 100.233 0 93.5 0H24.508zM80 57.863C80 66.663 73.436 72 62.543 72H44a2 2 0 01-2-2V24a2 2 0 012-2h18.437c9.083 0 15.044 4.92 15.044 12.474 0 5.302-4.01 10.049-9.119 10.88v.277C75.317 46.394 80 51.21 80 57.863zM60.521 28.34H49.948v14.934h8.905c6.884 0 10.68-2.772 10.68-7.727 0-4.643-3.264-7.207-9.012-7.207zM49.948 49.2v16.458H60.91c7.167 0 10.964-2.876 10.964-8.281 0-5.406-3.903-8.178-11.425-8.178H49.948z" fill="currentColor" />
                </svg>
                Bootstrap
            </a>
        </div>
    </twig:Navbar>
</div>
```

### Nav

Build navigation from lists, direct links, or dropdown items, and mark the current page explicitly.

```twig {"preview":true,"height":"500px"}
<div class="d-flex flex-column gap-3">
    <twig:Navbar expand="lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Navbar</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Pricing</a></li>
                    <li class="nav-item"><a class="nav-link disabled" aria-disabled="true">Disabled</a></li>
                </ul>
            </div>
        </div>
    </twig:Navbar>

    <twig:Navbar expand="lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Navbar</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                <div class="navbar-nav">
                    <a class="nav-link active" aria-current="page" href="#">Home</a>
                    <a class="nav-link" href="#">Features</a>
                    <a class="nav-link" href="#">Pricing</a>
                    <a class="nav-link disabled" aria-disabled="true">Disabled</a>
                </div>
            </div>
        </div>
    </twig:Navbar>

    <twig:Navbar expand="lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Navbar</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarNavDropdown">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Pricing</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Dropdown link</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Action</a></li>
                            <li><a class="dropdown-item" href="#">Another action</a></li>
                            <li><a class="dropdown-item" href="#">Something else here</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </twig:Navbar>
</div>
```

### Forms

Place search forms, input groups, and button groups inside navbars.

```twig {"preview":true,"height":"430px"}
<div class="d-flex flex-column gap-3">
    <twig:Navbar>
        <div class="container-fluid">
            <form class="d-flex" role="search">
                <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search">
                <button class="btn btn-outline-success" type="submit">Search</button>
            </form>
        </div>
    </twig:Navbar>

    <twig:Navbar>
        <div class="container-fluid">
            <a class="navbar-brand">Navbar</a>
            <form class="d-flex" role="search">
                <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search">
                <button class="btn btn-outline-success" type="submit">Search</button>
            </form>
        </div>
    </twig:Navbar>

    <twig:Navbar>
        <form class="container-fluid">
            <div class="input-group">
                <span class="input-group-text" id="basic-addon1">@</span>
                <input type="text" class="form-control" placeholder="Username" aria-label="Username" aria-describedby="basic-addon1">
            </div>
        </form>
    </twig:Navbar>

    <twig:Navbar>
        <form class="container-fluid justify-content-start">
            <button class="btn btn-outline-success me-2" type="button">Main button</button>
            <button class="btn btn-sm btn-outline-secondary" type="button">Smaller button</button>
        </form>
    </twig:Navbar>
</div>
```

### Text

Use `navbar-text` for vertically aligned text alongside navigation content.

```twig {"preview":true,"height":"300px"}
<div class="d-flex flex-column gap-3">
    <twig:Navbar>
        <div class="container-fluid">
            <span class="navbar-text">Navbar text with an inline element</span>
        </div>
    </twig:Navbar>

    <twig:Navbar expand="lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Navbar w/ text</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarText" aria-controls="navbarText" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarText">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Pricing</a></li>
                </ul>
                <span class="navbar-text">Navbar text with an inline element</span>
            </div>
        </div>
    </twig:Navbar>
</div>
```

### Color schemes

Use `data-bs-theme` through the `theme` prop and combine it with background utilities or custom colors.

```twig {"preview":true,"height":"330px"}
<div class="d-flex flex-column gap-3">
    <twig:Navbar background="dark" theme="dark" class="border-bottom border-body">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Navbar</a>
            <div class="navbar-nav flex-row gap-3">
                <a class="nav-link active" aria-current="page" href="#">Home</a>
                <a class="nav-link" href="#">Features</a>
                <a class="nav-link" href="#">Pricing</a>
            </div>
        </div>
    </twig:Navbar>

    <twig:Navbar background="primary" theme="dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Navbar</a>
            <div class="navbar-nav flex-row gap-3">
                <a class="nav-link active" aria-current="page" href="#">Home</a>
                <a class="nav-link" href="#">Features</a>
                <a class="nav-link" href="#">Pricing</a>
            </div>
        </div>
    </twig:Navbar>

    <twig:Navbar :background="null" theme="light" style="background-color: #e3f2fd;">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Navbar</a>
            <div class="navbar-nav flex-row gap-3">
                <a class="nav-link active" aria-current="page" href="#">Home</a>
                <a class="nav-link" href="#">Features</a>
                <a class="nav-link" href="#">Pricing</a>
            </div>
        </div>
    </twig:Navbar>
</div>
```

### Containers

Wrap a navbar in a container, or choose a responsive inner container to constrain its content.

```twig {"preview":true,"height":"240px"}
<div class="d-flex flex-column gap-3">
    <div class="container">
        <twig:Navbar expand="lg">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">Navbar</a>
            </div>
        </twig:Navbar>
    </div>

    <twig:Navbar expand="lg">
        <div class="container-md">
            <a class="navbar-brand" href="#">Navbar</a>
        </div>
    </twig:Navbar>
</div>
```

### Default placement

Keep the navbar in the normal document flow.

```twig {"preview":true,"height":"160px"}
<twig:Navbar>
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Default</a>
    </div>
</twig:Navbar>
```

### Fixed top

Fix the navbar to the top of the viewport and account for the removed document-flow space.

```twig {"preview":true,"height":"180px"}
<twig:Navbar class="fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Fixed top</a>
    </div>
</twig:Navbar>
```

### Fixed bottom

Fix the navbar to the bottom of the viewport.

```twig {"preview":true,"height":"180px"}
<twig:Navbar class="fixed-bottom">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Fixed bottom</a>
    </div>
</twig:Navbar>
```

### Sticky top

Keep the navbar at the top after it reaches that edge while scrolling.

```twig {"preview":true,"height":"180px"}
<twig:Navbar class="sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Sticky top</a>
    </div>
</twig:Navbar>
```

### Sticky bottom

Keep the navbar at the bottom after it reaches that edge while scrolling.

```twig {"preview":true,"height":"180px"}
<twig:Navbar class="sticky-bottom">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Sticky bottom</a>
    </div>
</twig:Navbar>
```

### Scrolling

Limit collapsed navigation height with `navbar-nav-scroll` and `--bs-scroll-height`.

```twig {"preview":true,"height":"300px"}
<twig:Navbar expand="lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Navbar scroll</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarScroll" aria-controls="navbarScroll" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarScroll">
            <ul class="navbar-nav me-auto my-2 my-lg-0 navbar-nav-scroll" style="--bs-scroll-height: 100px;">
                <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Link</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Link</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">Action</a></li>
                        <li><a class="dropdown-item" href="#">Another action</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#">Something else here</a></li>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link disabled" aria-disabled="true">Link</a></li>
            </ul>
            <form class="d-flex" role="search">
                <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search">
                <button class="btn btn-outline-success" type="submit">Search</button>
            </form>
        </div>
    </div>
</twig:Navbar>
```

### Toggler

Place the responsive toggler before or after the brand to control its alignment.

```twig {"preview":true,"height":"430px"}
<div class="d-flex flex-column gap-3">
    <twig:Navbar expand="lg">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTogglerDemo01" aria-controls="navbarTogglerDemo01" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarTogglerDemo01">
                <a class="navbar-brand" href="#">Hidden brand</a>
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Link</a></li>
                    <li class="nav-item"><a class="nav-link disabled" aria-disabled="true">Disabled</a></li>
                </ul>
                <form class="d-flex" role="search"><input class="form-control me-2" type="search" placeholder="Search" aria-label="Search"><button class="btn btn-outline-success" type="submit">Search</button></form>
            </div>
        </div>
    </twig:Navbar>

    <twig:Navbar expand="lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Navbar</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTogglerDemo02" aria-controls="navbarTogglerDemo02" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarTogglerDemo02">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Link</a></li>
                    <li class="nav-item"><a class="nav-link disabled" aria-disabled="true">Disabled</a></li>
                </ul>
                <form class="d-flex" role="search"><input class="form-control me-2" type="search" placeholder="Search" aria-label="Search"><button class="btn btn-outline-success" type="submit">Search</button></form>
            </div>
        </div>
    </twig:Navbar>

    <twig:Navbar expand="lg">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTogglerDemo03" aria-controls="navbarTogglerDemo03" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
            <a class="navbar-brand" href="#">Navbar</a>
            <div class="collapse navbar-collapse" id="navbarTogglerDemo03">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Link</a></li>
                    <li class="nav-item"><a class="nav-link disabled" aria-disabled="true">Disabled</a></li>
                </ul>
                <form class="d-flex" role="search"><input class="form-control me-2" type="search" placeholder="Search" aria-label="Search"><button class="btn btn-outline-success" type="submit">Search</button></form>
            </div>
        </div>
    </twig:Navbar>
</div>
```

### External content

Target collapsible content that sits outside the navbar while keeping the ARIA relationship explicit.

```twig {"preview":true,"height":"260px"}
<div class="collapse" id="navbarToggleExternalContent" data-bs-theme="dark">
    <div class="bg-dark p-4">
        <h5 class="text-body-emphasis h4">Collapsed content</h5>
        <span class="text-body-secondary">Toggleable via the navbar brand.</span>
    </div>
</div>
<twig:Navbar background="dark" theme="dark">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarToggleExternalContent" aria-controls="navbarToggleExternalContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
    </div>
</twig:Navbar>
```

### Offcanvas

Use an offcanvas drawer for a navbar that remains collapsed at every breakpoint.

```twig {"preview":true,"height":"240px"}
<twig:Navbar class="fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Offcanvas navbar</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="offcanvasNavbarLabel">Offcanvas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Link</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Dropdown</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Action</a></li>
                            <li><a class="dropdown-item" href="#">Another action</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#">Something else here</a></li>
                        </ul>
                    </li>
                </ul>
                <form class="d-flex mt-3" role="search">
                    <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search">
                    <button class="btn btn-outline-success" type="submit">Search</button>
                </form>
            </div>
        </div>
    </div>
</twig:Navbar>
```

### Responsive offcanvas

Expand the offcanvas navigation into a regular navbar at a selected breakpoint.

```twig {"preview":true,"height":"240px"}
<twig:Navbar expand="lg" class="fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Offcanvas navbar</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#navbarOffcanvasLg" aria-controls="navbarOffcanvasLg" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
        <div class="offcanvas offcanvas-end" tabindex="-1" id="navbarOffcanvasLg" aria-labelledby="navbarOffcanvasLgLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="navbarOffcanvasLgLabel">Navigation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#navbarOffcanvasLg" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Pricing</a></li>
                </ul>
            </div>
        </div>
    </div>
</twig:Navbar>
```

### Dark offcanvas

Keep the navbar, drawer, close button, and dropdown menu aligned with a dark theme.

```twig {"preview":true,"height":"240px"}
<twig:Navbar background="dark" theme="dark" class="fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Offcanvas dark navbar</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasDarkNavbar" aria-controls="offcanvasDarkNavbar" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
        <div class="offcanvas offcanvas-end text-bg-dark" tabindex="-1" id="offcanvasDarkNavbar" aria-labelledby="offcanvasDarkNavbarLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="offcanvasDarkNavbarLabel">Dark offcanvas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Link</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Dropdown</a>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-item" href="#">Action</a></li>
                            <li><a class="dropdown-item" href="#">Another action</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#">Something else here</a></li>
                        </ul>
                    </li>
                </ul>
                <form class="d-flex mt-3" role="search">
                    <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search">
                    <button class="btn btn-success" type="submit">Search</button>
                </form>
            </div>
        </div>
    </div>
</twig:Navbar>
```

## API Reference

::: api-reference
