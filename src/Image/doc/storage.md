# Storage

Deleting an `ImageAsset` through a built-in storage deletes both its original
and every variant path recorded in the asset. The operation is idempotent:
already missing files are ignored.

Both built-in storages and the storage router implement
`StreamStorageInterface`. Remote processing materializes an original once in a
private `ProcessingWorkspace`; streams are closed and the workspace is removed
after processing.

`ImageWriteSession` stages encoded local files under immutable, random
generation keys and publishes them through the stream contract. Existing keys
are never overwritten. If any write fails, only objects from the new generation
are deleted in reverse order. Object stores still do not provide a physical
multi-object transaction; the persisted `ImageAsset` is the publication
boundary.

## Concept

Storage is where processed variant files live. A storage is a named entry in config that maps a
name (e.g. `product_images`) to a concrete backend. Profiles reference a storage by name at
processing time; the `ImageAsset` records the storage name so the `UrlGenerator` can find the
right backend at render time.

## StorageInterface

```php
interface StorageInterface
{
    public function store(UploadedFile $file, string $storageName, ?string $directory = null): string;
    public function delete(ImageAsset $imageAsset): bool;
    public function exists(ImageAsset $imageAsset): bool;
    public function getPublicUrl(ImageAsset $imageAsset, ?string $variant = null): string;
    public function getFilePath(ImageAsset $imageAsset): string;
}
```

`store()` returns a path relative to the selected storage, for example
`/uximg_abc123.jpg`. The storage name is recorded separately on `ImageAsset`.
Together, `storageName` and `path` can be passed back to `getFilePath()` or
`getPublicUrl()`.

## Storage root

Local files are written under the `storage_root` bundle option (exposed
internally as the `ux_image.storage_root` container parameter), which
defaults to:

```
%kernel.project_dir%/var/ux-image
```

This is a **private** path under `var/`. It is not web-accessible by default. Files land at:

```
{storage_root}/{storage_name}/{path}
```

The storage root is a bundle configuration key. To change it:

```yaml
# config/packages/ux_image.yaml
ux_image:
    storage_root: '%kernel.project_dir%/var/media'
```

Because the root is private, serving files to the browser requires either a `public_url_prefix`
that maps to a web-served location, or a CDN (see below).

## Local storage

`LocalStorage` uses `symfony/filesystem` to write files. It is the default backend: a storage
that sets neither `flysystem_service` nor `adapter_service` is served locally. Such a storage
only needs a `public_url_prefix`, which is prepended to stored paths when generating URLs:

```yaml
# config/packages/ux_image.yaml
ux_image:
    storages:
        avatars:
            public_url_prefix: /uploads/avatars
```

Files for the `avatars` storage are written to `{storage_root}/avatars/…`. When a request comes
in, `getPublicUrl()` returns `public_url_prefix + '/' + path`.

## Flysystem storage

Flysystem storage delegates to a `League\Flysystem\FilesystemOperator`. Any Flysystem adapter
works: S3, GCS, Azure Blob, SFTP, etc. It requires `league/flysystem`.

```bash
composer require league/flysystem-bundle
composer require league/flysystem-aws-s3-v3
```

```yaml
# config/packages/flysystem.yaml
flysystem:
    storages:
        product.storage:
            adapter: aws
            options:
                client: aws_s3.client
                bucket: '%env(S3_BUCKET)%'
                prefix: uploads/products
```

```yaml
# config/packages/ux_image.yaml
ux_image:
    storages:
        products:
            flysystem_service: product.storage
            public_url_prefix: '%env(CDN_URL)%/uploads/products'
```

The `public_url_prefix` tells the `UrlGenerator` the base URL for this storage when no CDN builder
is configured.

## CDN integration

Both local and Flysystem storages can be fronted by a CDN, configured under `cdn:` in the storage
block. When a CDN is configured, the `UrlGenerator` delegates URL construction to a
`CdnUrlBuilderInterface` instead of prepending `public_url_prefix`.

```yaml
# config/packages/ux_image.yaml
ux_image:
    storages:
        products:
            flysystem_service: product.storage
            public_url_prefix: '%env(S3_URL)%' # fallback if no CDN builder matches
            cdn:
                provider: cloudinary
                base_url: https://res.cloudinary.com/mycloud/image/upload
```

The `cdn` block accepts exactly two keys: `provider` (`cloudinary` or `imgix`) and `base_url`
(required). There is no per-provider `options` key.

### Cloudinary

> Experimental integration: this builder only generates delivery URLs. UX
> Image does not upload originals to Cloudinary. The configured account must
> already be able to address or fetch each stored path.

`CloudinaryUrlBuilder` constructs transformation URLs. It always appends `f_auto,q_auto`
(Cloudinary auto-format and auto-quality):

```
https://res.cloudinary.com/mycloud/image/upload/w_600,h_400,c_crop,f_auto,q_auto/product.jpg
```

Transformations derived from the variant config:

| Variant config | Cloudinary param   |
| -------------- | ------------------ |
| `width`        | `w_`               |
| `height`       | `h_`               |
| `mode: crop`   | `c_crop`           |
| `mode: fit`    | `c_fit`            |
| `mode: fill`   | `c_fill`           |
| (always)       | `f_auto`, `q_auto` |

The CDN re-sizes and re-encodes on the first request and caches thereafter.

### Imgix

> Experimental integration: this builder only generates delivery URLs. The
> Imgix source must already expose each stored path.

`ImgixUrlBuilder` constructs query-string transformation URLs. It always appends
`auto=format,compress`:

```
https://myaccount.imgix.net/product.jpg?w=600&h=400&fit=crop&q=80&auto=format%2Ccompress
```

| Variant config | Imgix param            |
| -------------- | ---------------------- |
| `width`        | `w`                    |
| `height`       | `h`                    |
| `mode: crop`   | `fit=crop`             |
| `mode: fit`    | `fit=scale`            |
| `mode: fill`   | `fit=fillmax`          |
| `quality`      | `q`                    |
| (always)       | `auto=format,compress` |

### Custom CDN builder

Implement `CdnUrlBuilderInterface` and tag the service with `ux_image.cdn_url_builder`, providing
the provider name:

```php
// src/Image/BunnyUrlBuilder.php
namespace App\Image;

use Symfony\UX\Image\UrlGenerator\CdnUrlBuilderInterface;

final class BunnyUrlBuilder implements CdnUrlBuilderInterface
{
    public static function getProviderName(): string
    {
        return 'bunny';
    }

    public function buildUrl(
        string $baseUrl,
        string $path,
        array $profileConfig,
        array $variantConfig,
    ): string {
        $query = http_build_query(array_filter([
            'width' => $variantConfig['width'] ?? null,
            'height' => $variantConfig['height'] ?? null,
        ]));

        return rtrim($baseUrl, '/').'/'.ltrim($path, '/').('' !== $query ? '?'.$query : '');
    }
}
```

```yaml
# config/services.yaml
services:
    App\Image\BunnyUrlBuilder:
        tags: [{ name: ux_image.cdn_url_builder, provider: bunny }]
```

Set the same provider name on the storage:

```yaml
# config/packages/ux_image.yaml
ux_image:
    storages:
        media:
            cdn:
                provider: bunny
                base_url: 'https://example.b-cdn.net'
```

Provider names are extensible. `cloudinary` and `imgix` are merely the built-in
builders.

### Custom URL adapter

Storage and URL concerns are configured independently. `adapter_service`
selects a storage backend; `url_adapter` selects a tagged URL resolver:

```yaml
# config/packages/ux_image.yaml
ux_image:
    storages:
        private_media:
            adapter_service: App\Image\PrivateStorage
            url_adapter: signed
```

```yaml
# config/services.yaml
services:
    App\Image\SignedUrlAdapter:
        tags: [{ name: ux_image.storage_adapter, alias: signed }]
```

The service implements `UrlAdapterInterface`. An unknown configured adapter
fails URL generation instead of falling back to a public URL.

## URL caching

URL generation (especially with CDN transformation strings) can be cached:

```yaml
# config/packages/ux_image.yaml
ux_image:
    cache:
        enabled: true
        pool: cache.app # any PSR-6 cache pool
        ttl: 3600 # seconds
```

When enabled, `CachedUrlGenerator` decorates `UrlGenerator` and stores results in the pool. When
`cache.enabled` is `false`, the decorator is removed at compile time.

Enable this cache for URL adapters that perform meaningful work, such as
signing CDN transformation URLs. The built-in `generic` adapter only joins a
prefix and a path, so caching it usually adds more overhead than it removes.

## What to choose

| Scenario                           | Recommendation                      |
| ---------------------------------- | ----------------------------------- |
| Single-server app                  | Local storage                       |
| Multi-server or containerized app  | Flysystem (S3 or equivalent)        |
| High traffic, need edge delivery   | Flysystem + Cloudinary or Imgix CDN |
| Large originals, resize on-the-fly | Cloudinary or Imgix as CDN          |
| Custom cloud provider              | Implement `CdnUrlBuilderInterface`  |

## Reference

### Storage config keys

| Key                 | Required   | Description                                                          |
| ------------------- | ---------- | -------------------------------------------------------------------- |
| `flysystem_service` | no         | Flysystem operator service ID (`league/flysystem-bundle`)            |
| `adapter_service`   | no         | Custom storage adapter service ID                                    |
| `url_adapter`       | no         | Tagged URL adapter name; defaults to `generic`                       |
| `public_url_prefix` | no         | Base URL prepended to paths when no CDN builder is set               |
| `cdn.provider`      | no         | Tagged CDN builder name (`cloudinary` and `imgix` are built in)      |
| `cdn.base_url`      | with `cdn` | Base URL passed to the CDN builder (required when a provider is set) |

A storage that sets neither `flysystem_service` nor `adapter_service` is served by the built-in
local filesystem backend.

> **Note**
> The filesystem root is configured with `ux_image.storage_root`
> (default `%kernel.project_dir%/var/ux-image`), not a per-storage config key.
