# Configuration

## Processing budgets

```yaml
ux_image:
    limits:
        max_input_bytes: 20000000
        max_width: 12000
        max_height: 12000
        max_megapixels: 40
        max_variants: 12
        max_output_megapixels: 80
```

Input budgets are checked from the image contents before storage. Variant count
and fully specified output dimensions are checked while the container is built.
The actual encoded dimensions are accumulated during processing, so proportional
and `scaleDown` outputs cannot bypass `max_output_megapixels`.

## Doctrine type

The global DBAL type is opt-in:

```yaml
ux_image:
    doctrine_type: true
```

Enabling it registers `image_asset`; container compilation fails with an
installation hint when DoctrineBundle is absent. Applications can instead
register `ImageAssetType` themselves.

## Full reference

```yaml
# config/packages/ux_image.yaml
ux_image:

    # Image processing driver. Selects the built-in processing backend.
    # gd      uses the ext-gd PHP extension (default).
    # imagick uses intervention/image with the Imagick driver.
    # vips    uses intervention/image with the intervention/image-driver-vips driver.
    driver: gd  # gd | imagick | vips | a custom processor driver name

    # Root directory used by built-in local storages.
    storage_root: '%kernel.project_dir%/var/ux-image'

    # Register the global Doctrine DBAL image_asset type.
    doctrine_type: false

    # Processing budgets, enforced before decode and during generation.
    limits:
        max_input_bytes: 20000000
        max_width: 12000
        max_height: 12000
        max_megapixels: 40
        max_variants: 12
        max_output_megapixels: 80

    # Service ID of a pre-configured custom Intervention Image driver
    # (Intervention\Image\Interfaces\DriverInterface). When set, it takes
    # precedence over `driver` and routes processing through Intervention.
    driver_service: ~

    # Service ID of a complete ImageProcessorInterface implementation. This
    # bypasses driver-based processor selection and takes precedence over both
    # driver and driver_service.
    processor_service: ~

    # Default value for the sizes="" attribute on <img> and <source> elements.
    # Can be overridden per-profile or per render call.
    default_sizes: 100vw

    # Default format priority used when a profile does not set its own list.
    # Ordered by preference. Affects <source> tag order in <picture>.
    preferred_formats: [avif, webp, jpeg, jpg, png]

    # URL caching. When enabled, wraps UrlGenerator with CachedUrlGenerator.
    cache:
        enabled: false
        pool: cache.app    # any PSR-6 cache pool service ID
        ttl: 3600          # seconds

    # Named storages. A storage may reference a Flysystem filesystem
    # (flysystem_service) or a custom adapter_service. A storage that sets
    # neither is served by the built-in local filesystem backend.
    storages:
        <name>:
            # Service ID from league/flysystem-bundle. Requires league/flysystem.
            flysystem_service: ~
            # Custom storage adapter service ID (alternative to Flysystem).
            adapter_service: ~
            # Tagged URL adapter name. Use "storage" to delegate public URL
            # generation to StorageInterface::getPublicUrl().
            url_adapter: generic
            # Public URL prefix prepended to stored paths when no CDN is set.
            public_url_prefix: ~
            # Optional CDN integration. When set, URL generation delegates to a
            # CdnUrlBuilderInterface instead of prepending public_url_prefix.
            cdn:
                provider: ~   # cloudinary | imgix | a custom tagged builder name
                base_url: ~   # required when a CDN provider is set

    # Processing profiles.
    profiles:
        <name>:
            # immediate generates now, deferred stores only the original,
            # async dispatches through ImageProcessingDispatcherInterface.
            processing: immediate

            # Optional safe storage-relative directory for this profile.
            directory: ~

            # HTML sizes attribute for this profile. Overrides default_sizes.
            sizes: ~

            # Optional per-profile format priority for rendering.
            preferred_formats: []

            # Output formats to generate for each variant. Must be non-empty.
            formats: [webp, jpeg]

            # Named variants. Each variant produces one file per format.
            variants:
                <name>:
                    # Target width in pixels. At least one positive dimension
                    # (width or height) is required.
                    width: ~
                    # Target height in pixels. When omitted, height is
                    # calculated proportionally.
                    height: ~
                    # Resize mode.
                    # crop: crop to the target rectangle
                    # fit:  fit inside the target box (default)
                    # fill: fill the target box
                    mode: fit
                    # Encoding quality, 1-100.
                    quality: 80
                    # Density descriptor for srcset (e.g. "1x", "2x", "3x").
                    density: ~
                    # Media query for art direction (e.g. "(max-width: 768px)").
                    media: ~
                    # Crop position (top, center, bottom, left, right,
                    # or "50% 30%").
                    position: center
```

> **Note**
> The filesystem root for local storage is the `storage_root` configuration,
> which defaults to
> `%kernel.project_dir%/var/ux-image` (a private path under `var/`, not web-accessible).
> See [Storage](storage.md).

## Defaults table

| Key | Default | Notes |
|---|---|---|
| `driver` | `gd` | Built-in: `gd`, `imagick`, `vips`; custom processors may expose another name |
| `driver_service` | `null` | Custom Intervention driver service ID; takes precedence over `driver` |
| `processor_service` | `null` | Complete custom processor service; bypasses driver selection |
| `default_sizes` | `100vw` | HTML `sizes` attribute fallback |
| `storage_root` | `%kernel.project_dir%/var/ux-image` | Private root used by built-in local storages |
| `preferred_formats` | `[avif, webp, jpeg, jpg, png]` | Controls `<source>` order |
| `doctrine_type` | `false` | Registers the global `image_asset` DBAL type when enabled |
| `cache.enabled` | `false` | |
| `cache.pool` | `cache.app` | Any PSR-6 pool |
| `cache.ttl` | `3600` | Seconds |
| `limits.max_input_bytes` | `20000000` | Maximum encoded input size |
| `limits.max_width` | `12000` | Maximum source width |
| `limits.max_height` | `12000` | Maximum source height |
| `limits.max_megapixels` | `40` | Maximum decoded source pixels |
| `limits.max_variants` | `12` | Maximum named variants per profile |
| `limits.max_output_megapixels` | `80` | Total encoded output pixel budget |
| `profiles.<name>.processing` | `immediate` | `immediate`, `deferred` or experimental `async` |
| `profiles.<name>.directory` | `null` | Safe storage-relative directory |
| `profiles.<name>.sizes` | `null` | Overrides `default_sizes` during rendering |
| `profiles.<name>.preferred_formats` | global order | Profile-specific `<source>` order |
| `profiles.<name>.formats` | `[webp, jpeg]` | Must be non-empty |
| `profiles.<name>.variants.<name>.mode` | `fit` | `crop` \| `fit` \| `fill` |
| `profiles.<name>.variants.<name>.quality` | `80` | 1-100 |
| `profiles.<name>.variants.<name>.position` | `center` | Crop position |

The built-in `generic` URL adapter composes `public_url_prefix` and the stored
path. Use `url_adapter: storage` when a custom storage implementation owns
public URL generation through `StorageInterface::getPublicUrl()`. Custom URL
adapters implement `UrlAdapterInterface` and are tagged
`ux_image.storage_adapter` with an `alias`.

## Validation rules

- A storage may set `flysystem_service` or `adapter_service`. A storage with neither is served
  by the built-in local filesystem backend (files under `ux_image.storage_root`).
- When a `cdn.provider` is set, `cdn.base_url` is required.
- A profile's `formats` list must contain at least one format.
- A profile `directory` must be a non-empty relative path without absolute,
  backslash, NUL, `.` or `..` segments.
- Every variant must define a positive `width`, a positive `height`, or both.
- A profile may define at most `limits.max_variants`; its generated pixels may
  not exceed `limits.max_output_megapixels`.
- Variant `quality` must be between 1 and 100.
- `driver: imagick` or `driver: vips`, or setting `driver_service`, requires `intervention/image`
  (checked at container compile time). `driver: vips` additionally requires
  `intervention/image-driver-vips` (plus the libvips system library and `ext-ffi`).
- The default `driver: gd` fails container compilation when `ext-gd` is unavailable.
  A custom `processor_service` owns its runtime dependencies and codec validation;
  `ux:image:validate` still probes its configured storages.

## Built-in `responsive_default` profile

When no profile named `responsive_default` is defined, the bundle injects one:

```yaml
profiles:
    responsive_default:
        processing: immediate
        # Safe on every supported GD installation; modern codecs are opt-in.
        formats: [jpeg]
        variants:
            mobile:  { width: 640,  mode: fit, quality: 80 }
            tablet:  { width: 1024, mode: fit, quality: 85 }
            desktop: { width: 1920, mode: fit, quality: 90 }
```

This means you can call `ux_picture($asset)` without configuring any profile, as long as the
asset was processed with this default profile.

## Common patterns

### Multiple profiles over one storage

```yaml
# config/packages/ux_image.yaml
ux_image:
    storages:
        uploads:
            public_url_prefix: /uploads   # local filesystem backend, no adapter needed

    profiles:
        avatar:
            formats: [webp, jpeg]
            variants:
                small: { width: 64,  height: 64,  mode: crop }
                large: { width: 256, height: 256, mode: crop }

        blog_cover:
            formats: [avif, webp, jpeg]
            variants:
                thumb:  { width: 400 }
                medium: { width: 800 }
                full:   { width: 1600 }
```

The storage a variant is written to is chosen at processing time (the `$storage`
argument to `ImageProcessorInterface::process()`, default `default_public`), not on the
profile.

### S3 storage with Cloudinary CDN

```yaml
# config/packages/ux_image.yaml
ux_image:
    storages:
        media:
            flysystem_service: app.s3.filesystem
            public_url_prefix: '%env(S3_PUBLIC_URL)%'
            cdn:
                provider: cloudinary
                base_url: 'https://res.cloudinary.com/%env(CLOUDINARY_CLOUD)%/image/upload'
```

### Deferred processing for large imports

```yaml
# config/packages/ux_image.yaml
ux_image:
    profiles:
        import:
            processing: deferred  # generate variants later
            formats: [avif, webp, jpeg]
            variants:
                sm: { width: 400 }
                md: { width: 800 }
                lg: { width: 1600 }
```

```bash
# After the import batch completes:
php bin/console ux:image:regenerate --image-profile=import --storage=product_images
```

### Changing `preferred_formats`

`preferred_formats` controls the `<source>` order in `<picture>` only. Every
processing profile defines its own non-empty `formats` list:

```yaml
# config/packages/ux_image.yaml
ux_image:
    preferred_formats: [webp, jpeg]  # no AVIF, e.g. if your server can't encode it
```

> **Tip**
> Put the smallest format first (e.g. `avif`) in `preferred_formats` for best compression.

## Per-use overrides

| What | Global config | Render-time override |
|---|---|---|
| `sizes` attribute | `default_sizes` | `ux_picture($asset, { sizes: '…' })` |
| Lazy loading | always lazy by default | `ux_picture($asset, { lazy: false })` |
| Fetch priority | `auto` | `ux_picture($asset, { fetchpriority: 'high' })` |
| Variant filter | all variants | `ux_picture($asset, { variant: 'hero' })` |
| Format order | `preferred_formats` | not overridable at render time |

## CDN bridges

Cloudinary and Imgix activate only when a storage explicitly configures its
provider. Base URLs must be HTTP(S) URLs without embedded credentials. Asset
path segments are encoded, dimensions and quality accept bounded integers only,
and unknown transformation modes fail instead of silently changing semantics.
Cloudinary uses automatic format negotiation for original image URLs. For a
persisted variant, it preserves that variant's explicit format so the response
matches the persisted MIME type advertised by the rendered `<source>` element.
Private-delivery signing belongs in a custom `CdnUrlBuilderInterface`.
