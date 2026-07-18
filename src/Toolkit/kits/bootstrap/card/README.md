# Card

Build flexible content containers with optional headers, footers, images, and contextual styles.

```twig {"preview":true,"height":"480px"}
{% set image_url = 'https://images.unsplash.com/photo-1535189043414-47a3c49a0bed?auto=format&fit=crop&w=720&q=80' %}

<twig:Card style="width: 22rem;">
    <twig:Card:Image src="{{ image_url }}" alt="A quiet mountain landscape" />
    <twig:Card:Body>
        <twig:Card:Title>Explore the outdoors</twig:Card:Title>
        <twig:Card:Subtitle class="mb-2 text-body-secondary">Weekend inspiration</twig:Card:Subtitle>
        <twig:Card:Text>Discover a new trail and make time for a change of scenery.</twig:Card:Text>
        <a href="#" class="btn btn-primary">Find a trail</a>
        <twig:Card:Link href="#">View guide</twig:Card:Link>
    </twig:Card:Body>
    <twig:Card:Footer class="text-body-secondary">Updated today</twig:Card:Footer>
</twig:Card>
```

## Installation

::: installation

## Usage

```twig
<twig:Card style="max-width: 24rem;">
    <twig:Card:Header>Featured</twig:Card:Header>
    <twig:Card:Body>
        <twig:Card:Title>Card title</twig:Card:Title>
        <twig:Card:Text>Some quick example text to build on the card title.</twig:Card:Text>
    </twig:Card:Body>
    <twig:Card:Footer class="text-body-secondary">2 days ago</twig:Card:Footer>
</twig:Card>
```

## Accessibility

Choose a semantic heading level for each card title that fits the surrounding page hierarchy.

Do not rely on background, text, or border color alone to communicate meaning. Keep sufficient contrast and provide an equivalent text cue when color carries information.

## Examples

Combine an image, title, supporting text, and action in a fixed-width card.

```twig {"preview":true,"height":"400px"}
{% set image_url = 'https://images.unsplash.com/photo-1535189043414-47a3c49a0bed?auto=format&fit=crop&w=720&q=80' %}

<twig:Card style="width: 18rem;">
    <twig:Card:Image src="{{ image_url }}" alt="A quiet mountain landscape" />
    <twig:Card:Body>
        <twig:Card:Title>Card title</twig:Card:Title>
        <twig:Card:Text>Some quick example text to build on the card title and make up the bulk of the card's content.</twig:Card:Text>
        <a href="#" class="btn btn-primary">Go somewhere</a>
    </twig:Card:Body>
</twig:Card>
```

### Content types

Mix card bodies, titles, subtitles, text, links, images, and list groups.

```twig {"preview":true,"height":"470px"}
{% set image_url = 'https://images.unsplash.com/photo-1535189043414-47a3c49a0bed?auto=format&fit=crop&w=720&q=80' %}

<div class="d-flex flex-wrap gap-3">
    <twig:Card style="width: 18rem;">
        <twig:Card:Body>
            <twig:Card:Title>Card title</twig:Card:Title>
            <twig:Card:Subtitle class="mb-2 text-body-secondary">Card subtitle</twig:Card:Subtitle>
            <twig:Card:Text>Some quick example text to build on the card title.</twig:Card:Text>
            <twig:Card:Link href="#">Card link</twig:Card:Link>
            <twig:Card:Link href="#">Another link</twig:Card:Link>
        </twig:Card:Body>
    </twig:Card>

    <twig:Card style="width: 18rem;">
        <twig:Card:Image src="{{ image_url }}" alt="A quiet mountain landscape" />
        <twig:Card:Body>
            <twig:Card:Text>Text, images, list groups, and links can be freely combined.</twig:Card:Text>
        </twig:Card:Body>
        <ul class="list-group list-group-flush">
            <li class="list-group-item">An item</li>
            <li class="list-group-item">A second item</li>
            <li class="list-group-item">A third item</li>
        </ul>
        <twig:Card:Body>
            <twig:Card:Link href="#">Card link</twig:Card:Link>
        </twig:Card:Body>
    </twig:Card>
</div>
```

### Header and footer

Add optional headers and footers, including semantic heading and quote content.

```twig {"preview":true,"height":"420px"}
<div class="d-grid gap-3">
    <twig:Card>
        <twig:Card:Header>Featured</twig:Card:Header>
        <twig:Card:Body>
            <twig:Card:Title>Special title treatment</twig:Card:Title>
            <twig:Card:Text>With supporting text below as a natural lead-in to additional content.</twig:Card:Text>
            <a href="#" class="btn btn-primary">Go somewhere</a>
        </twig:Card:Body>
    </twig:Card>

    <twig:Card class="text-center">
        <twig:Card:Header tag="h5">Featured</twig:Card:Header>
        <twig:Card:Body>
            <figure class="mb-0">
                <blockquote class="blockquote"><p>A well-known quote, contained in a blockquote element.</p></blockquote>
                <figcaption class="blockquote-footer mb-0">Someone famous in <cite title="Source Title">Source Title</cite></figcaption>
            </figure>
        </twig:Card:Body>
        <twig:Card:Footer class="text-body-secondary">2 days ago</twig:Card:Footer>
    </twig:Card>
</div>
```

### Sizing

Size cards with Bootstrap's grid, width utilities, or custom CSS.

```twig {"preview":true,"height":"190px"}
<div class="row">
    <div class="col-sm-6 mb-3 mb-sm-0">
        <twig:Card>
            <twig:Card:Body>
                <twig:Card:Title>Grid-sized card</twig:Card:Title>
                <twig:Card:Text>The surrounding Bootstrap grid controls this card's width.</twig:Card:Text>
            </twig:Card:Body>
        </twig:Card>
    </div>
    <div class="col-sm-6">
        <twig:Card class="w-75">
            <twig:Card:Body>
                <twig:Card:Title>Utility-sized card</twig:Card:Title>
                <twig:Card:Text>Width utilities or custom CSS can size a card directly.</twig:Card:Text>
            </twig:Card:Body>
        </twig:Card>
    </div>
</div>
```

### Text alignment

Apply Bootstrap text alignment utilities to a card or one of its sections.

```twig {"preview":true,"height":"440px"}
<div class="d-flex flex-wrap gap-3">
    {% for alignment, label in {'': 'Start', 'text-center': 'Center', 'text-end': 'End'} %}
        <twig:Card class="{{ alignment }}" style="width: 18rem;">
            <twig:Card:Body>
                <twig:Card:Title>{{ label }} aligned</twig:Card:Title>
                <twig:Card:Text>Use Bootstrap text utilities on the whole card or an individual section.</twig:Card:Text>
                <a href="#" class="btn btn-primary">Go somewhere</a>
            </twig:Card:Body>
        </twig:Card>
    {% endfor %}
</div>
```

### Navigation

Place tabs or pills in the card header.

```twig {"preview":true,"height":"350px"}
<div class="d-grid gap-3">
    {% for style in ['tabs', 'pills'] %}
        <twig:Card class="text-center">
            <twig:Card:Header>
                <ul class="nav nav-{{ style }} card-header-{{ style }}">
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Active</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Link</a></li>
                    <li class="nav-item"><a class="nav-link disabled" aria-disabled="true">Disabled</a></li>
                </ul>
            </twig:Card:Header>
            <twig:Card:Body>
                <twig:Card:Title>Special title treatment</twig:Card:Title>
                <twig:Card:Text>Card headers can contain Bootstrap tab or pill navigation.</twig:Card:Text>
            </twig:Card:Body>
        </twig:Card>
    {% endfor %}
</div>
```

### Image caps

Position an image at the top or bottom edge of a card.

```twig {"preview":true,"height":"350px"}
{% set image_url = 'https://images.unsplash.com/photo-1535189043414-47a3c49a0bed?auto=format&fit=crop&w=960&q=80' %}

<div class="row row-cols-1 row-cols-sm-2 g-3">
    <div class="col">
        <twig:Card class="h-100">
            <twig:Card:Image src="{{ image_url }}" alt="A quiet mountain landscape" />
            <twig:Card:Body>
                <twig:Card:Title>Top image cap</twig:Card:Title>
                <twig:Card:Text>Images can sit at either end of a card.</twig:Card:Text>
            </twig:Card:Body>
        </twig:Card>
    </div>
    <div class="col">
        <twig:Card class="h-100">
            <twig:Card:Body>
                <twig:Card:Title>Bottom image cap</twig:Card:Title>
                <twig:Card:Text>The bottom position matches the lower card corners.</twig:Card:Text>
            </twig:Card:Body>
            <twig:Card:Image src="{{ image_url }}" alt="A quiet mountain landscape" position="bottom" />
        </twig:Card>
    </div>
</div>
```

### Image overlays

Use an image as the card background and place concise, high-contrast content over it.

```twig {"preview":true,"height":"410px"}
{% set image_url = 'https://images.unsplash.com/photo-1535189043414-47a3c49a0bed?auto=format&fit=crop&w=960&q=80' %}

<twig:Card class="text-bg-dark">
    <twig:Card:Image src="{{ image_url }}" alt="A quiet mountain landscape" position="background" />
    <twig:Card:Overlay>
        <twig:Card:Title>Card title</twig:Card:Title>
        <twig:Card:Text>Overlay text should remain shorter than the image height and maintain sufficient contrast.</twig:Card:Text>
        <twig:Card:Text><small>Last updated 3 mins ago</small></twig:Card:Text>
    </twig:Card:Overlay>
</twig:Card>
```

### Horizontal

Combine the component with Bootstrap's responsive grid to create a horizontal card.

```twig {"preview":true,"height":"210px"}
{% set image_url = 'https://images.unsplash.com/photo-1535189043414-47a3c49a0bed?auto=format&fit=crop&w=720&q=80' %}

<twig:Card class="mb-3" style="max-width: 540px;">
    <div class="row g-0">
        <div class="col-sm-4">
            <img src="{{ image_url }}" class="img-fluid rounded-start h-100 object-fit-cover" alt="A quiet mountain landscape">
        </div>
        <div class="col-sm-8">
            <twig:Card:Body>
                <twig:Card:Title>Card title</twig:Card:Title>
                <twig:Card:Text>This card becomes horizontal at the small breakpoint.</twig:Card:Text>
                <twig:Card:Text><small class="text-body-secondary">Last updated 3 mins ago</small></twig:Card:Text>
            </twig:Card:Body>
        </div>
    </div>
</twig:Card>
```

### Background and color

Use Bootstrap's `text-bg-*` helpers for contextual card styles.

```twig {"preview":true,"height":"720px"}
<div class="d-flex flex-wrap gap-3">
    {% for color in ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'] %}
        <twig:Card class="text-bg-{{ color }}" style="max-width: 18rem;">
            <twig:Card:Header>Header</twig:Card:Header>
            <twig:Card:Body>
                <twig:Card:Title>{{ color|title }} card title</twig:Card:Title>
                <twig:Card:Text>Some quick example text to build on the card title.</twig:Card:Text>
            </twig:Card:Body>
        </twig:Card>
    {% endfor %}
</div>
```

### Borders

Change border and text colors independently with utility classes.

```twig {"preview":true,"height":"720px"}
<div class="d-flex flex-wrap gap-3">
    {% for color in ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'] %}
        <twig:Card class="border-{{ color }}" style="max-width: 18rem;">
            <twig:Card:Header class="border-{{ color }}">Header</twig:Card:Header>
            <twig:Card:Body class="text-{{ color }}">
                <twig:Card:Title>{{ color|title }} card title</twig:Card:Title>
                <twig:Card:Text>Use border utilities independently from backgrounds.</twig:Card:Text>
            </twig:Card:Body>
        </twig:Card>
    {% endfor %}
</div>
```

### Mixins utilities

Customize header and footer borders or remove their background color.

```twig {"preview":true,"height":"250px"}
<twig:Card class="border-success" style="max-width: 18rem;">
    <twig:Card:Header class="bg-transparent border-success">Header</twig:Card:Header>
    <twig:Card:Body class="text-success">
        <twig:Card:Title>Success card title</twig:Card:Title>
        <twig:Card:Text>Header and footer borders can be customized independently.</twig:Card:Text>
    </twig:Card:Body>
    <twig:Card:Footer class="bg-transparent border-success">Footer</twig:Card:Footer>
</twig:Card>
```

### Card groups

Render a set of attached cards with equal width and height.

```twig {"preview":true,"height":"370px"}
{% set image_url = 'https://images.unsplash.com/photo-1535189043414-47a3c49a0bed?auto=format&fit=crop&w=720&q=80' %}

<div class="card-group">
    {% for length in ['This content is a little bit longer.', 'This card has supporting text.', 'This card has even longer content to demonstrate equal heights.'] %}
        <twig:Card>
            <twig:Card:Image src="{{ image_url }}" alt="A quiet mountain landscape" />
            <twig:Card:Body>
                <twig:Card:Title>Card title</twig:Card:Title>
                <twig:Card:Text>{{ length }}</twig:Card:Text>
            </twig:Card:Body>
            <twig:Card:Footer><small class="text-body-secondary">Last updated 3 mins ago</small></twig:Card:Footer>
        </twig:Card>
    {% endfor %}
</div>
```

### Grid cards

Use responsive grid columns and `h-100` cards for a flexible multi-card layout.

```twig {"preview":true,"height":"360px"}
{% set image_url = 'https://images.unsplash.com/photo-1535189043414-47a3c49a0bed?auto=format&fit=crop&w=720&q=80' %}

<div class="row row-cols-1 row-cols-sm-3 g-4">
    {% for length in ['A longer card with supporting text.', 'A short card.', 'A card with supporting text and an aligned footer.'] %}
        <div class="col">
            <twig:Card class="h-100">
                <twig:Card:Image src="{{ image_url }}" alt="A quiet mountain landscape" />
                <twig:Card:Body>
                    <twig:Card:Title>Card title</twig:Card:Title>
                    <twig:Card:Text>{{ length }}</twig:Card:Text>
                </twig:Card:Body>
                <twig:Card:Footer><small class="text-body-secondary">Last updated 3 mins ago</small></twig:Card:Footer>
            </twig:Card>
        </div>
    {% endfor %}
</div>
```

## API Reference

::: api-reference
