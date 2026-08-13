# Architecture

## Overview

UX Image is built around a pipeline: an uploaded file enters as an `UploadedFile`, passes through
processing and storage, and exits as an `ImageAsset` value object. Rendering reads the `ImageAsset`
later. It is decoupled from the upload path.

```
Upload time
───────────────────────────────────────────────────────────────────────────────
UploadedFile
    │
    ▼
ChainImageProcessor
    │   reads profile config: formats[], variants{}, driver
    │   delegates to GdImageProcessor or InterventionImageProcessor
    │
    ├─── resize + convert → variant files
    │       e.g. product_abc123_thumbnail.avif, .webp, .jpeg
    │            product_abc123_card.avif, .webp, .jpeg
    │            product_abc123_hero.avif, .webp, .jpeg
    │
    ▼
StorageInterface (LocalStorage or FlysystemStorage)
    │   writes variant files
    │   returns storage name + public URL prefix
    │
    ▼
ImageAsset  (final readonly class)
    │   storageName, path, originalFilename, mimeType, width, height
    │   variants: [format => list<ImageSource{name, path, dimensions, media}>]
    │
    ▼ persist (Doctrine JSON column via ImageAssetType)

Render time
───────────────────────────────────────────────────────────────────────────────
ImageAsset
    │
    ▼
UrlGenerator
    │   resolves storageName → storage config
    │   if cdn: configured → CdnUrlBuilder (Cloudinary or Imgix)
    │   else   → public_url_prefix + path
    │
    ▼
DefaultImageRenderer
    │   reads preferred_formats → <source> order
    │   single-ratio: one srcset per format (width descriptors: 300w 600w 1200w)
    │   art direction: one <source> per variant media query and format
    │   builds RenderedImage
    │
    ▼
RenderedImage
    │   toHtml()   → <picture> + <source> tags + <img> fallback
    │   toImgHtml() → plain <img srcset="…">
    │
    ▼
HTML output
```

## Key classes

### ImageAsset

`final readonly class`, the single object passed around after upload. It is the output of
processing and the input to rendering. Holds no URLs; only paths relative to the storage root.

### ImageSourceSet

Produced by `ImageAsset::getImageSourceSet()`. Encapsulates the variant structure and knows
whether variants use width or density descriptors and groups their per-variant media queries. The
renderer asks `ImageSourceSet` for format-specific `srcset` strings.

### Image processor and driver contracts

`ImageProcessorInterface` is the application-facing orchestration contract: it
processes an upload or regenerates an asset. `ImageDriverInterface` extends it
with the low-level support, resize, conversion and inspection primitives required
by a complete tagged backend.
`ChainImageProcessor` implements both contracts and delegates to the first tagged
backend supporting the configured driver. `GdImageProcessor` is always registered;
`InterventionImageProcessor` is registered when `intervention/image` is installed
and serves the `imagick` and `vips` drivers.

### StorageInterface

Two built-in implementations: `LocalStorage` (uses `symfony/filesystem`, writes under the
`ux_image.storage_root` parameter populated from `storage_root` configuration
(default `%kernel.project_dir%/var/ux-image`) and
`FlysystemStorage` (wraps a `League\Flysystem\FilesystemOperator`). Both expose `store()`,
`delete()`, `exists()`, `getPublicUrl()`, and `getFilePath()`. Storage never
enumerates application assets; regeneration uses an application provider.

### UrlGenerator / CachedUrlGenerator

`UrlGenerator` resolves a storage name + variant path to a public URL. When a CDN is configured
it delegates to a `CdnUrlBuilderInterface` (Cloudinary or Imgix). `CachedUrlGenerator` wraps
`UrlGenerator` with a PSR-6 cache pool.

### DefaultImageRenderer

Reads `preferred_formats` from config, builds one `<source>` per format (ordered from most
preferred to least), then appends the `<img>` fallback in the least-preferred format. Handles
`lazy`, `fetchPriority`, `decoding`, `sizes`, `alt`, `class`, and `variant` filter.

### Doctrine ImageAssetType

`ImageAssetType extends JsonType`. The column stores the result of `ImageAsset::toArray()` as JSON.
On read, `ImageAsset::fromArray()` reconstructs the object. Registration is
explicit and opt-in:

```yaml
# config/packages/ux_image.yaml
ux_image:
    doctrine_type: true
```

## Configuration anatomy

```
ux_image:
    driver: gd                    # built-in or custom processor driver name
    driver_service: ~             # optional custom Intervention driver service id
    processor_service: ~          # optional complete ImageProcessorInterface service id
    preferred_formats: [avif, webp, jpeg, jpg, png]
    default_sizes: 100vw          # <img sizes="…"> default

    cache:                        # optional URL caching
        enabled: true
        pool: cache.app
        ttl: 3600

    storages:
        <name>:                   # arbitrary name
            flysystem_service: …  # Flysystem filesystem service id, or
            adapter_service: …    # a custom storage adapter service id
            url_adapter: generic  # independent tagged URL adapter name
            public_url_prefix: …  # base URL when no CDN is set
            cdn:
                provider: cloudinary | imgix | custom
                base_url: …

    profiles:
        <name>:                   # arbitrary name, passed to process()
            formats: [avif, webp, jpeg]
            variants:
                <name>:           # variant name embedded in the filename
                    width: 600
                    height: 400   # optional
                    mode: crop | fit | fill
            processing: immediate       # immediate | deferred | async
            sizes: 100vw          # overrides default_sizes for this profile
```

## Extension points

| Extension point | How |
|---|---|
| Complete custom pipeline | Implement `ImageProcessorInterface`, set `processor_service` |
| Tagged processing backend | Implement `ImageDriverInterface`, tag `ux_image.processor` |
| Custom CDN | Implement `CdnUrlBuilderInterface`, tag `ux_image.cdn_url_builder` |
| Custom storage | Implement `StorageInterface`, wire manually |
| Custom renderer | Implement `ImageRendererInterface`, override the service alias |
| Override a built-in theme | Copy template to `templates/bundles/UXImageBundle/` |
