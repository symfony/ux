# Rendering

Rendering is metadata-only: it never inspects a local file or performs remote
I/O. Persist dimensions in `ImageAsset` when layout stability matters. Modern
AVIF/WebP variants are emitted as `<source>` elements, while `<img>` uses a
JPEG/PNG variant or the original as its universal fallback.

Arbitrary safe attributes can be passed through the `attributes:` constructor argument
(`id`, `data-*`, `aria-*`, and similar). Event-handler attributes and attributes
owned by the renderer such as `src`, `width` and `loading` are rejected.

## Concept

Rendering reads an `ImageAsset` and produces HTML. The rendering layer is entirely separate from
processing and storage: it runs at request time, not upload time. The renderer asks the
`UrlGenerator` for public URLs per variant per format, then assembles a `RenderedImage` object
that can produce `<picture>` or `<img>` markup.

The browser, not PHP, chooses one candidate:

```text
ImageAsset
    ├── profile sizes + preferred format order
    ├── variant paths + widths/densities + media conditions
    └── original dimensions
             │
             ▼
        RenderedImage
             │
             ├── <source type media srcset sizes>
             └── <img src srcset sizes width height loading ...>
                                      │
                                      ▼
                          browser selects one resource
```

Rendering does not check that files still exist. Storage publication and
application cleanup must keep the persisted asset and its objects consistent.

## Usage

### Twig functions

With `TwigBundle` active, two functions are registered globally and require no
template import:

```twig
{# templates/product/show.html.twig #}
{# <picture> with <source> per format + <img> fallback #}
{{ ux_picture(product.image) }}

{# Plain <img> with srcset, no <source> tags #}
{{ ux_image(product.image) }}
```

Both accept an options array as the second argument:

```twig
{# templates/product/show.html.twig #}
{{ ux_picture(product.image, {
    alt: 'Product photo',
    lazy: false,
    fetchpriority: 'high',
    sizes: '(min-width: 768px) 50vw, 100vw',
    class: 'product-hero',
    variant: 'hero',
}) }}
```

### TwigComponent

When `symfony/ux-twig-component` is installed **and**
`TwigComponentBundle` is registered:

```twig
{# templates/product/show.html.twig #}
<twig:ux:image :src="product.image" alt="Product photo" />

<twig:ux:image
    :src="product.image"
    alt="Product photo"
    variant="thumbnail"
    :lazy="false"
    fetchpriority="high"
    class="avatar"
/>
```

The `<twig:ux:image>` component renders via `ux_picture()` internally. The template is at
`templates/components/Image.html.twig` and can be overridden under
`templates/bundles/UXImageBundle/components/Image.html.twig`.

## HTML output

### `ux_picture()` output

The package documentation test creates a deterministic asset with
`ImageAssetFactory::responsive(formats: ['avif', 'webp', 'jpeg'], widths:
[300, 600, 1200])`, renders it through `DefaultImageRenderer`, and compares the
result with this fixture. Its test URL generator prefixes asset paths with
`/media`.

<!-- fixture: responsive-picture -->

```html
<picture>
    <source
        type="image/avif"
        srcset="
            /media/fixtures/image-300.avif   300w,
            /media/fixtures/image-600.avif   600w,
            /media/fixtures/image-1200.avif 1200w
        "
        sizes="100vw" />
    <source
        type="image/webp"
        srcset="
            /media/fixtures/image-300.webp   300w,
            /media/fixtures/image-600.webp   600w,
            /media/fixtures/image-1200.webp 1200w
        "
        sizes="100vw" />
    <source
        type="image/jpeg"
        srcset="
            /media/fixtures/image-300.jpeg   300w,
            /media/fixtures/image-600.jpeg   600w,
            /media/fixtures/image-1200.jpeg 1200w
        "
        sizes="100vw" />
    <img
        src="/media/fixtures/image-300.jpeg"
        sizes="100vw"
        alt="Product photo"
        loading="lazy"
        fetchpriority="auto"
        decoding="async"
        srcset="
            /media/fixtures/image-300.jpeg   300w,
            /media/fixtures/image-600.jpeg   600w,
            /media/fixtures/image-1200.jpeg 1200w
        "
        width="1600"
        height="1000" />
</picture>
```

`<source>` tags are ordered by `preferred_formats`, including the JPEG source
when JPEG variants exist. The `<img>` fallback uses the first JPEG variant in
the source set. Its `width` and `height` come from the original `ImageAsset`
metadata, not from that fallback variant.

### `ux_image()` output

<!-- fixture: responsive-img -->

```html
<img
    src="/media/fixtures/image-300.jpeg"
    alt="Product photo"
    loading="lazy"
    fetchpriority="auto"
    decoding="async"
    srcset="
        /media/fixtures/image-300.jpeg   300w,
        /media/fixtures/image-600.jpeg   600w,
        /media/fixtures/image-1200.jpeg 1200w
    "
    sizes="100vw"
    width="1600"
    height="1000" />
```

No `<picture>` wrapper, no format negotiation. Uses the default format only.

### Review the rendered contract

For every important image placement, check:

- `alt` describes the image's purpose, or is deliberately empty for decoration;
- `width` and `height` are present to reserve layout space;
- `sizes` matches the real CSS layout rather than the source dimensions;
- the first `<source>` is the preferred supported format;
- `<img>` remains a universal fallback;
- above-the-fold images are eager/high priority and off-screen images stay lazy.

Do not cache an `ImageAsset` as rendered HTML when storage or CDN configuration
may change. Cache the page as usual if its invalidation follows the owning
model. UX Image's optional `cache:` setting caches generated URLs, not the
`<picture>` string; see [Storage](storage.md#url-caching).

## Options

Both `ux_picture()` and `ux_image()` accept the same options:

| Option          | Type           | Default | Description                                              |
| --------------- | -------------- | ------- | -------------------------------------------------------- |
| `alt`           | `string`       | `''`    | `alt` attribute value                                    |
| `lazy`          | `bool`         | `true`  | `loading="lazy"` when true, `loading="eager"` when false |
| `fetchpriority` | `string`       | `auto`  | `fetchpriority` attribute (`high`, `low`, `auto`)        |
| `sizes`         | `string`       | `100vw` | `sizes` attribute for `<source>` and `<img>`             |
| `class`         | `string`       | `''`    | CSS class on the `<img>` element                         |
| `decoding`      | `string`       | `async` | `decoding` attribute (`async`, `sync`, `auto`)           |
| `variant`       | `string\|null` | `null`  | Filter to a single named variant                         |

### `variant` option

When `variant` is set, only sources from that named variant are included in the srcset:

```twig
{# Only the thumbnail variant, all its formats #}
{{ ux_picture(product.image, { variant: 'thumbnail' }) }}
```

The renderer filters variants by matching the persisted `name` field to `thumbnail`. A manually
constructed URL-only variant without a `name` remains renderable, but cannot be selected by name.

### Performance options

For above-the-fold images, disable lazy loading and set a high fetch priority:

```twig
{# templates/product/show.html.twig #}
{{ ux_picture(product.image, {
    lazy: false,
    fetchpriority: 'high',
    decoding: 'sync',
}) }}
```

> **Tip**
> For above-the-fold images, set `lazy: false` and `fetchpriority: 'high'` to improve LCP.

This produces:

```html
<img loading="eager" fetchpriority="high" decoding="sync" … />
```

## Art-directed variants

When canonical variants carry `media` conditions, the renderer derives one
`<source>` per media condition and format. Art direction does not introduce a
second top-level persistence shape:

```html
<picture>
    <source type="image/avif" media="(max-width: 640px)" srcset="/uploads/product-mobile.avif" />
    <source
        type="image/avif"
        media="(min-width: 641px)"
        srcset="/uploads/product-desktop.avif 1200w, /uploads/product-card.avif 600w" />
    <source type="image/webp" media="(max-width: 640px)" srcset="/uploads/product-mobile.webp" />
    <source
        type="image/webp"
        media="(min-width: 641px)"
        srcset="/uploads/product-desktop.webp 1200w, /uploads/product-card.webp 600w" />
    <img src="/uploads/product-card.jpeg" … />
</picture>
```

`isMultiRatio()` on `ImageSourceSet` controls which rendering path is taken.
Media-constrained sources are emitted before an unconditional source, so the
fallback cannot mask a later art-directed candidate. Within one `srcset`,
every candidate uses the same descriptor family. Widths are used when every
candidate has a width; densities are used only when every candidate has a
density. Duplicate descriptors collapse to the last configured candidate.

## Custom rendering

When you need complete control over the HTML, use the `RenderedImage` object directly:

```php
// src/Controller/ProductController.php
use Symfony\UX\Image\Renderer\ImageRendererInterface;
use Symfony\UX\Image\Renderer\ImageRenderOptions;

$options = new ImageRenderOptions(
    sizes: '(min-width: 1024px) 33vw, 100vw',
    alt: 'Product',
    lazy: false,
    fetchPriority: 'high',
);

$rendered = $renderer->render($asset, $options);

// In a controller, for API responses:
return new Response($rendered->toHtml());
```

In Twig, both functions return a string. Pipe through `|raw` is not needed as the functions
already output safe HTML.

## Overriding templates

The TwigComponent template lives at:
`vendor/symfony/ux-image/templates/components/Image.html.twig`

Override it by creating:
`templates/bundles/UXImageBundle/components/Image.html.twig`

The override receives the same `rendered` variable (`RenderedImage` object) with `toHtml()` and
`toImgHtml()` methods.

## Reference

### Twig functions

| Function                                      | Output                           |
| --------------------------------------------- | -------------------------------- |
| `ux_picture(ImageAsset, array $options = [])` | `<picture>` with `<source>` tags |
| `ux_image(ImageAsset, array $options = [])`   | `<img>` with `srcset`            |

### `RenderedImage` methods

| Method                                   | Description                        |
| ---------------------------------------- | ---------------------------------- |
| `toHtml(): string`                       | Full `<picture>` markup            |
| `toPictureHtml(): string`                | Alias of `toHtml()`                |
| `toImgHtml(): string`                    | `<img>` only markup                |
| `getAsset(): ImageAsset`                 | Persisted asset used for rendering |
| `getSources(): array`                    | Ordered `<source>` metadata        |
| `getFallbackSrc(): string`               | Fallback URL                       |
| `getFallbackSrcset(): ?string`           | Fallback candidate list            |
| `getWidth(): ?int` / `getHeight(): ?int` | Intrinsic dimensions               |
| `getOptions(): ImageRenderOptions`       | Effective render options           |

### `ImageRenderOptions` constructor

```php
new ImageRenderOptions(
    sizes: '100vw',
    alt: '',
    lazy: true,
    fetchPriority: 'auto',
    class: '',
    decoding: 'async',
    variant: null,
    srcset: null,
    attributes: ['data-testid' => 'product-image'],
)
```

`attributes` is part of the PHP renderer API. The Twig functions expose the
named options in the table above; use an application wrapper or the PHP API for
additional `data-*` and `aria-*` attributes.

### TwigComponent props

| Prop            | Type                 | Default      | Maps to                                       |
| --------------- | -------------------- | ------------ | --------------------------------------------- |
| `src`           | `ImageAsset`         | _(required)_ | `$asset`                                      |
| `alt`           | `string`             | `''`         | `ImageRenderOptions` argument `alt`           |
| `class`         | `string\|null`       | `null`       | `ImageRenderOptions` argument `class`         |
| `variant`       | `string\|null`       | `null`       | `ImageRenderOptions` argument `variant`       |
| `lazy`          | `bool`               | `true`       | `ImageRenderOptions` argument `lazy`          |
| `srcset`        | `list<string>\|null` | `null`       | `ImageRenderOptions` argument `srcset`        |
| `sizes`         | `string\|null`       | `null`       | `ImageRenderOptions` argument `sizes`         |
| `fetchpriority` | `string\|null`       | `null`       | `ImageRenderOptions` argument `fetchPriority` |
| `decoding`      | `string`             | `async`      | `ImageRenderOptions` argument `decoding`      |
