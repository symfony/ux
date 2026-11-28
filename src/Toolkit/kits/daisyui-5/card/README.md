# Card

Cards are used to group and display content in a way that is easily readable.

```twig {"preview":true}
<twig:Card class="w-96 bg-base-100 shadow-sm">
    <figure>
        <img src="https://img.daisyui.com/images/stock/photo-1606107557195-0e29a4b5b4aa.webp" alt="Shoes" />
    </figure>
    <twig:Card:Body>
        <twig:Card:Title>Card Title</twig:Card:Title>
        <p>A card component has a figure, a body part, and inside body there are title and actions parts</p>
        <twig:Card:Actions class="justify-end">
            <twig:Button color="primary">Buy Now</twig:Button>
        </twig:Card:Actions>
    </twig:Card:Body>
</twig:Card>
```

## Installation

::: installation

## Usage

```twig
<twig:Card
    variant="default | border | dash"
    size="xs | sm | md | lg | xl"
    side="false | true"
    imageFull="false | true"
    as="div | label"
>
    <figure>
        <img src="..." alt="..." />
    </figure>
    <twig:Card:Body>
        <twig:Card:Title as="h2 | h3 | ...">Card Title</twig:Card:Title>
        <p>Card content</p>
        <twig:Card:Actions>
            <twig:Button>Action</twig:Button>
        </twig:Card:Actions>
    </twig:Card:Body>
</twig:Card>
```

## Examples

### Pricing Card

```twig {"preview":true}
<twig:Card class="w-96 bg-base-100 shadow-sm">
    <twig:Card:Body>
        <span class="badge badge-xs badge-warning">Most Popular</span>
        <div class="flex justify-between">
            <h2 class="text-3xl font-bold">Premium</h2>
            <span class="text-xl">$29/mo</span>
        </div>
        <ul class="mt-6 flex flex-col gap-2 text-xs">
            <li>
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4 me-2 inline-block text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                <span>High-resolution image generation</span>
            </li>
            <li>
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4 me-2 inline-block text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                <span>Customizable style templates</span>
            </li>
            <li>
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4 me-2 inline-block text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                <span>Batch processing capabilities</span>
            </li>
            <li>
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4 me-2 inline-block text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                <span>AI-driven image enhancements</span>
            </li>
            <li class="opacity-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4 me-2 inline-block text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                <span class="line-through">Seamless cloud integration</span>
            </li>
            <li class="opacity-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4 me-2 inline-block text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                <span class="line-through">Real-time collaboration tools</span>
            </li>
        </ul>
        <div class="mt-6">
            <twig:Button color="primary" block>Subscribe</twig:Button>
        </div>
    </twig:Card:Body>
</twig:Card>
```

### Sizes

```twig {"preview":true}
<div class="flex flex-col gap-4">
    <twig:Card size="xs" class="w-96 bg-base-100 shadow-sm">
        <twig:Card:Body>
            <twig:Card:Title>Xsmall Card</twig:Card:Title>
            <p>A card component has a figure, a body part, and inside body there are title and actions parts</p>
            <twig:Card:Actions class="justify-end">
                <twig:Button color="primary">Buy Now</twig:Button>
            </twig:Card:Actions>
        </twig:Card:Body>
    </twig:Card>
    <twig:Card size="sm" class="w-96 bg-base-100 shadow-sm">
        <twig:Card:Body>
            <twig:Card:Title>Small Card</twig:Card:Title>
            <p>A card component has a figure, a body part, and inside body there are title and actions parts</p>
            <twig:Card:Actions class="justify-end">
                <twig:Button color="primary">Buy Now</twig:Button>
            </twig:Card:Actions>
        </twig:Card:Body>
    </twig:Card>
    <twig:Card class="w-96 bg-base-100 shadow-sm">
        <twig:Card:Body>
            <twig:Card:Title>Medium Card</twig:Card:Title>
            <p>A card component has a figure, a body part, and inside body there are title and actions parts</p>
            <twig:Card:Actions class="justify-end">
                <twig:Button color="primary">Buy Now</twig:Button>
            </twig:Card:Actions>
        </twig:Card:Body>
    </twig:Card>
    <twig:Card size="lg" class="w-96 bg-base-100 shadow-sm">
        <twig:Card:Body>
            <twig:Card:Title>Large Card</twig:Card:Title>
            <p>A card component has a figure, a body part, and inside body there are title and actions parts</p>
            <twig:Card:Actions class="justify-end">
                <twig:Button color="primary">Buy Now</twig:Button>
            </twig:Card:Actions>
        </twig:Card:Body>
    </twig:Card>
    <twig:Card size="xl" class="w-96 bg-base-100 shadow-sm">
        <twig:Card:Body>
            <twig:Card:Title>Xlarge Card</twig:Card:Title>
            <p>A card component has a figure, a body part, and inside body there are title and actions parts</p>
            <twig:Card:Actions class="justify-end">
                <twig:Button color="primary">Buy Now</twig:Button>
            </twig:Card:Actions>
        </twig:Card:Body>
    </twig:Card>
</div>
```

### Border

```twig {"preview":true}
<twig:Card variant="border" class="w-96 bg-base-100">
    <twig:Card:Body>
        <twig:Card:Title>Card Title</twig:Card:Title>
        <p>A card component has a figure, a body part, and inside body there are title and actions parts</p>
        <twig:Card:Actions class="justify-end">
            <twig:Button color="primary">Buy Now</twig:Button>
        </twig:Card:Actions>
    </twig:Card:Body>
</twig:Card>
```

### Dash

```twig {"preview":true}
<twig:Card variant="dash" class="w-96 bg-base-100">
    <twig:Card:Body>
        <twig:Card:Title>Card Title</twig:Card:Title>
        <p>A card component has a figure, a body part, and inside body there are title and actions parts</p>
        <twig:Card:Actions class="justify-end">
            <twig:Button color="primary">Buy Now</twig:Button>
        </twig:Card:Actions>
    </twig:Card:Body>
</twig:Card>
```

### With Badge

```twig {"preview":true}
<twig:Card class="w-96 bg-base-100 shadow-sm">
    <figure>
        <img src="https://img.daisyui.com/images/stock/photo-1606107557195-0e29a4b5b4aa.webp" alt="Shoes" />
    </figure>
    <twig:Card:Body>
        <twig:Card:Title>
            Card Title
            <span class="badge badge-secondary">NEW</span>
        </twig:Card:Title>
        <p>A card component has a figure, a body part, and inside body there are title and actions parts</p>
        <twig:Card:Actions class="justify-end">
            <span class="badge badge-outline">Fashion</span>
            <span class="badge badge-outline">Products</span>
        </twig:Card:Actions>
    </twig:Card:Body>
</twig:Card>
```

### With Bottom Image

```twig {"preview":true}
<twig:Card class="w-96 bg-base-100 shadow-sm">
    <twig:Card:Body>
        <twig:Card:Title>Card Title</twig:Card:Title>
        <p>A card component has a figure, a body part, and inside body there are title and actions parts</p>
    </twig:Card:Body>
    <figure>
        <img src="https://img.daisyui.com/images/stock/photo-1606107557195-0e29a4b5b4aa.webp" alt="Shoes" />
    </figure>
</twig:Card>
```

### With Centered Content And Paddings

```twig {"preview":true}
<twig:Card class="w-96 bg-base-100 shadow-sm">
    <figure class="px-10 pt-10">
        <img src="https://img.daisyui.com/images/stock/photo-1606107557195-0e29a4b5b4aa.webp" alt="Shoes" class="rounded-xl" />
    </figure>
    <twig:Card:Body class="items-center text-center">
        <twig:Card:Title>Card Title</twig:Card:Title>
        <p>A card component has a figure, a body part, and inside body there are title and actions parts</p>
        <twig:Card:Actions>
            <twig:Button color="primary">Buy Now</twig:Button>
        </twig:Card:Actions>
    </twig:Card:Body>
</twig:Card>
```

### With Image Overlay

```twig {"preview":true}
<twig:Card imageFull class="w-96 bg-base-100 shadow-sm">
    <figure>
        <img src="https://img.daisyui.com/images/stock/photo-1606107557195-0e29a4b5b4aa.webp" alt="Shoes" />
    </figure>
    <twig:Card:Body>
        <twig:Card:Title>Card Title</twig:Card:Title>
        <p>A card component has a figure, a body part, and inside body there are title and actions parts</p>
        <twig:Card:Actions class="justify-end">
            <twig:Button color="primary">Buy Now</twig:Button>
        </twig:Card:Actions>
    </twig:Card:Body>
</twig:Card>
```

### With No Image

```twig {"preview":true}
<twig:Card class="w-96 bg-base-100 shadow-sm">
    <twig:Card:Body>
        <twig:Card:Title>Card title!</twig:Card:Title>
        <p>A card component has a figure, a body part, and inside body there are title and actions parts</p>
        <twig:Card:Actions class="justify-end">
            <twig:Button color="primary">Buy Now</twig:Button>
        </twig:Card:Actions>
    </twig:Card:Body>
</twig:Card>
```

### With Custom Color

```twig {"preview":true}
<twig:Card class="w-96 bg-primary text-primary-content">
    <twig:Card:Body>
        <twig:Card:Title>Card title!</twig:Card:Title>
        <p>A card component has a figure, a body part, and inside body there are title and actions parts</p>
        <twig:Card:Actions class="justify-end">
            <twig:Button>Buy Now</twig:Button>
        </twig:Card:Actions>
    </twig:Card:Body>
</twig:Card>
```

### Centered With Neutral Color

```twig {"preview":true}
<twig:Card class="w-96 bg-neutral text-neutral-content">
    <twig:Card:Body class="items-center text-center">
        <twig:Card:Title>Cookies!</twig:Card:Title>
        <p>We are using cookies for no reason.</p>
        <twig:Card:Actions class="justify-end">
            <twig:Button color="primary">Accept</twig:Button>
            <twig:Button variant="ghost">Deny</twig:Button>
        </twig:Card:Actions>
    </twig:Card:Body>
</twig:Card>
```

### With Action On Top

```twig {"preview":true}
<twig:Card class="w-96 bg-base-100 shadow-sm">
    <twig:Card:Body>
        <twig:Card:Actions class="justify-end">
            <twig:Button shape="square" size="sm">
                <svg aria-label="Close" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </twig:Button>
        </twig:Card:Actions>
        <p>We are using cookies for no reason.</p>
    </twig:Card:Body>
</twig:Card>
```

### With Image On Side

```twig {"preview":true}
<twig:Card side class="bg-base-100 shadow-sm">
    <figure>
        <img src="https://img.daisyui.com/images/stock/photo-1635805737707-575885ab0820.webp" alt="Movie" />
    </figure>
    <twig:Card:Body>
        <twig:Card:Title>New movie is released!</twig:Card:Title>
        <p>Click the button to watch on Jetflix app.</p>
        <twig:Card:Actions class="justify-end">
            <twig:Button color="primary">Watch</twig:Button>
        </twig:Card:Actions>
    </twig:Card:Body>
</twig:Card>
```

### Responsive

This card is vertical on small screens and horizontal from the `lg` breakpoint.

```twig {"preview":true}
<twig:Card class="bg-base-100 shadow-sm lg:card-side">
    <figure>
        <img src="https://img.daisyui.com/images/stock/photo-1494232410401-ad00d5433cfa.webp" alt="Album" />
    </figure>
    <twig:Card:Body>
        <twig:Card:Title>New album is released!</twig:Card:Title>
        <p>Click the button to listen on Spotiwhy app.</p>
        <twig:Card:Actions class="justify-end">
            <twig:Button color="primary">Listen</twig:Button>
        </twig:Card:Actions>
    </twig:Card:Body>
</twig:Card>
```

### Selectable

Render the card as a `label` to make it select its checkbox or radio input.

```twig {"preview":true}
<div class="flex flex-wrap items-start gap-3">
    <twig:Card as="label" class="bg-accent text-accent-content">
        <input type="checkbox" name="card-urgent-1" />
        <twig:Card:Body>
            <twig:Card:Title>Urgent</twig:Card:Title>
            <p>Same day delivery</p>
        </twig:Card:Body>
    </twig:Card>
    <div class="join rounded-selector bg-base-300">
        <twig:Card as="label" class="join-item">
            <input type="radio" value="sm" name="card-size-1" />
            <twig:Card:Body>
                <twig:Card:Title>Size SM</twig:Card:Title>
                <p>Available</p>
            </twig:Card:Body>
        </twig:Card>
        <twig:Card as="label" class="join-item">
            <input type="radio" value="md" name="card-size-1" disabled />
            <twig:Card:Body class="opacity-60">
                <twig:Card:Title>Size MD</twig:Card:Title>
                <p>Not available</p>
            </twig:Card:Body>
        </twig:Card>
        <twig:Card as="label" class="join-item">
            <input type="radio" value="lg" name="card-size-1" />
            <twig:Card:Body>
                <twig:Card:Title>Size LG</twig:Card:Title>
                <p>Short supply</p>
            </twig:Card:Body>
        </twig:Card>
    </div>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col gap-8">
    <twig:Card side class="bg-base-100 shadow-sm" dir="rtl">
        <figure>
            <img src="https://img.daisyui.com/images/stock/photo-1635805737707-575885ab0820.webp" alt="فيلم" />
        </figure>
        <twig:Card:Body>
            <twig:Card:Title>تم إصدار فيلم جديد!</twig:Card:Title>
            <p>انقر على الزر للمشاهدة على التطبيق.</p>
            <twig:Card:Actions class="justify-end">
                <twig:Button color="primary">مشاهدة</twig:Button>
            </twig:Card:Actions>
        </twig:Card:Body>
    </twig:Card>
    <twig:Card side class="bg-base-100 shadow-sm" dir="rtl">
        <figure>
            <img src="https://img.daisyui.com/images/stock/photo-1635805737707-575885ab0820.webp" alt="סרט" />
        </figure>
        <twig:Card:Body>
            <twig:Card:Title>סרט חדש יצא!</twig:Card:Title>
            <p>לחץ על הכפתור כדי לצפות באפליקציה.</p>
            <twig:Card:Actions class="justify-end">
                <twig:Button color="primary">צפה</twig:Button>
            </twig:Card:Actions>
        </twig:Card:Body>
    </twig:Card>
</div>
```

## API Reference

::: api-reference
