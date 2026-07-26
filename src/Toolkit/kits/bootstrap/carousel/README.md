# Carousel

Cycle through images, text, and other content with optional controls and indicators.

```twig {"preview":true}
<twig:Carousel id="demo-carousel" indicators class="w-100">
    <twig:block name="indicators">
        <button type="button" data-bs-target="#demo-carousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
        <button type="button" data-bs-target="#demo-carousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
        <button type="button" data-bs-target="#demo-carousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
    </twig:block>
    <twig:Carousel:Item active>
        <div class="d-flex align-items-center justify-content-center bg-primary text-white fs-1" style="height: 12rem">First slide</div>
    </twig:Carousel:Item>
    <twig:Carousel:Item>
        <div class="d-flex align-items-center justify-content-center bg-success text-white fs-1" style="height: 12rem">Second slide</div>
    </twig:Carousel:Item>
    <twig:Carousel:Item>
        <div class="d-flex align-items-center justify-content-center bg-danger text-white fs-1" style="height: 12rem">Third slide</div>
    </twig:Carousel:Item>
</twig:Carousel>
```

## Installation

::: installation

## Usage

```twig
<twig:Carousel id="featured-content">
    <twig:Carousel:Item active>
        <img src="..." class="d-block w-100" alt="...">
    </twig:Carousel:Item>
    <twig:Carousel:Item>
        <img src="..." class="d-block w-100" alt="...">
    </twig:Carousel:Item>
</twig:Carousel>
```

## Accessibility

Every carousel needs a unique `id`, and every indicator and control must target that same value. Mark exactly one initial `Carousel:Item` as `active`; otherwise the carousel is not visible.

Carousels that start automatically can be difficult for people using assistive technology or who need more time to read. Prefer user-controlled playback, or provide a pause control when autoplay is required. Write meaningful alternative text for images and keep the previous and next labels understandable in the page language.

## Examples

### Basic example

Cycle through three slides with Bootstrap's previous and next controls.

```twig {"preview":true}
<twig:Carousel id="basic-carousel" class="w-100">
    <twig:Carousel:Item active>
        <div class="d-flex align-items-center justify-content-center bg-primary text-white fs-1" style="height: 12rem">First slide</div>
    </twig:Carousel:Item>
    <twig:Carousel:Item>
        <div class="d-flex align-items-center justify-content-center bg-secondary text-white fs-1" style="height: 12rem">Second slide</div>
    </twig:Carousel:Item>
    <twig:Carousel:Item>
        <div class="d-flex align-items-center justify-content-center bg-dark text-white fs-1" style="height: 12rem">Third slide</div>
    </twig:Carousel:Item>
</twig:Carousel>
```

### Indicators

Add buttons that identify and select each slide.

```twig {"preview":true}
<twig:Carousel id="indicators-carousel" indicators class="w-100">
    <twig:block name="indicators">
        <button type="button" data-bs-target="#indicators-carousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
        <button type="button" data-bs-target="#indicators-carousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
        <button type="button" data-bs-target="#indicators-carousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
    </twig:block>
    <twig:Carousel:Item active><div class="bg-primary text-white p-5 text-center fs-2">First slide</div></twig:Carousel:Item>
    <twig:Carousel:Item><div class="bg-success text-white p-5 text-center fs-2">Second slide</div></twig:Carousel:Item>
    <twig:Carousel:Item><div class="bg-danger text-white p-5 text-center fs-2">Third slide</div></twig:Carousel:Item>
</twig:Carousel>
```

### Captions

Overlay responsive headings and supporting text on each slide.

```twig {"preview":true}
<twig:Carousel id="captions-carousel" indicators class="w-100">
    <twig:block name="indicators">
        <button type="button" data-bs-target="#captions-carousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
        <button type="button" data-bs-target="#captions-carousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
        <button type="button" data-bs-target="#captions-carousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
    </twig:block>
    <twig:Carousel:Item active>
        <div class="bg-primary" style="height: 12rem"></div>
        <div class="carousel-caption d-none d-md-block"><h5>First slide label</h5><p>Representative placeholder content for the first slide.</p></div>
    </twig:Carousel:Item>
    <twig:Carousel:Item>
        <div class="bg-success" style="height: 12rem"></div>
        <div class="carousel-caption d-none d-md-block"><h5>Second slide label</h5><p>Representative placeholder content for the second slide.</p></div>
    </twig:Carousel:Item>
    <twig:Carousel:Item>
        <div class="bg-danger" style="height: 12rem"></div>
        <div class="carousel-caption d-none d-md-block"><h5>Third slide label</h5><p>Representative placeholder content for the third slide.</p></div>
    </twig:Carousel:Item>
</twig:Carousel>
```

### Crossfade

Replace the horizontal movement with a fade transition.

```twig {"preview":true}
<twig:Carousel id="crossfade-carousel" fade class="w-100">
    <twig:Carousel:Item active><div class="bg-primary text-white p-5 text-center fs-2">First slide</div></twig:Carousel:Item>
    <twig:Carousel:Item><div class="bg-success text-white p-5 text-center fs-2">Second slide</div></twig:Carousel:Item>
    <twig:Carousel:Item><div class="bg-danger text-white p-5 text-center fs-2">Third slide</div></twig:Carousel:Item>
</twig:Carousel>
```

### Autoplaying carousels

Start cycling as soon as the page loads with `ride="carousel"`.

```twig {"preview":true}
<twig:Carousel id="autoplay-carousel" ride="carousel" class="w-100">
    <twig:Carousel:Item active><div class="bg-primary text-white p-5 text-center fs-2">First slide</div></twig:Carousel:Item>
    <twig:Carousel:Item><div class="bg-success text-white p-5 text-center fs-2">Second slide</div></twig:Carousel:Item>
    <twig:Carousel:Item><div class="bg-danger text-white p-5 text-center fs-2">Third slide</div></twig:Carousel:Item>
</twig:Carousel>
```

### Ride after interaction

Start cycling only after the user first interacts with the carousel.

```twig {"preview":true}
<twig:Carousel id="interaction-carousel" ride="true" class="w-100">
    <twig:Carousel:Item active><div class="bg-primary text-white p-5 text-center fs-2">First slide</div></twig:Carousel:Item>
    <twig:Carousel:Item><div class="bg-success text-white p-5 text-center fs-2">Second slide</div></twig:Carousel:Item>
    <twig:Carousel:Item><div class="bg-danger text-white p-5 text-center fs-2">Third slide</div></twig:Carousel:Item>
</twig:Carousel>
```

### Individual item interval

Give individual slides a longer or shorter display interval.

```twig {"preview":true}
<twig:Carousel id="interval-carousel" ride="carousel" class="w-100">
    <twig:Carousel:Item active :interval="10000"><div class="bg-primary text-white p-5 text-center fs-2">10 seconds</div></twig:Carousel:Item>
    <twig:Carousel:Item :interval="2000"><div class="bg-success text-white p-5 text-center fs-2">2 seconds</div></twig:Carousel:Item>
    <twig:Carousel:Item><div class="bg-danger text-white p-5 text-center fs-2">Default interval</div></twig:Carousel:Item>
</twig:Carousel>
```

### Autoplay without controls

Autoplay a carousel without rendering previous and next buttons.

```twig {"preview":true}
<twig:Carousel id="uncontrolled-carousel" ride="carousel" :controls="false" class="w-100">
    <twig:Carousel:Item active><div class="bg-primary text-white p-5 text-center fs-2">First slide</div></twig:Carousel:Item>
    <twig:Carousel:Item><div class="bg-success text-white p-5 text-center fs-2">Second slide</div></twig:Carousel:Item>
    <twig:Carousel:Item><div class="bg-danger text-white p-5 text-center fs-2">Third slide</div></twig:Carousel:Item>
</twig:Carousel>
```

### Disable touch swiping

Keep controls active while disabling touch gestures.

```twig {"preview":true}
<twig:Carousel id="no-touch-carousel" :touch="false" class="w-100">
    <twig:Carousel:Item active><div class="bg-primary text-white p-5 text-center fs-2">First slide</div></twig:Carousel:Item>
    <twig:Carousel:Item><div class="bg-success text-white p-5 text-center fs-2">Second slide</div></twig:Carousel:Item>
    <twig:Carousel:Item><div class="bg-danger text-white p-5 text-center fs-2">Third slide</div></twig:Carousel:Item>
</twig:Carousel>
```

### Dark variant

Use Bootstrap's dark color mode for controls and indicators on a light slide.

```twig {"preview":true}
<twig:Carousel id="dark-carousel" theme="dark" indicators class="w-100">
    <twig:block name="indicators">
        <button type="button" data-bs-target="#dark-carousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
        <button type="button" data-bs-target="#dark-carousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
    </twig:block>
    <twig:Carousel:Item active><div class="bg-light text-dark p-5 text-center fs-2">First slide</div></twig:Carousel:Item>
    <twig:Carousel:Item><div class="bg-light text-dark p-5 text-center fs-2">Second slide</div></twig:Carousel:Item>
</twig:Carousel>
```

## API Reference

::: api-reference
