# Installation

## Requirements

- PHP 8.4+
- Symfony 7.4+
- Symfony Console (installed as a package dependency)
- TwigBundle (installed as a package dependency)
- GD extension (default). The `imagick` and `vips` drivers instead require `intervention/image`.

## Install the bundle

```bash
composer require symfony/ux-image
```

> **Note**
> Register `TwigBundle` and `UXImageBundle` in `config/bundles.php`, then create
> `config/packages/ux_image.yaml` before using `ux_picture()` or `ux_image()`.

## Optional dependencies

| Dependency | When needed |
|---|---|
| `symfony/ux-twig-component` | `<twig:ux:image>` TwigComponent |
| `league/flysystem-bundle` | Flysystem-backed storage (S3, GCS, Azure …) |
| `symfony/cache` | URL caching via `cache:` config key |
| `intervention/image` | `imagick` or `vips` driver, or a custom `driver_service` |
| `intervention/image-driver-vips` | `vips` driver (also needs libvips and `ext-ffi`) |
| `doctrine/doctrine-bundle` | Persisting `ImageAsset` via the `image_asset` Doctrine type |

None are required for the core processing, storage, and Twig-function rendering
pipeline with the default GD driver.

Installing an optional package does not activate its integration. Register the
matching bundle in `config/bundles.php` (Flex normally does this), then enable
the UX Image configuration that uses it:

- `TwigComponentBundle` must be active before `<twig:ux:image>` is registered;
- `FlysystemBundle` must be active, or the application must register the
  configured `FilesystemOperator` service itself;
- `DoctrineBundle` must be active and `doctrine_type: true` must be set before
  the DBAL type is registered.

## Quick start

Configure `default_public`, validate that an uploaded value is one file,
process it with the built-in `responsive_default` profile, then pass the
returned asset to Twig:

```php
// src/Controller/PhotoController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Image\Processor\ImageProcessorInterface;

final class PhotoController extends AbstractController
{
    #[Route('/photo', methods: ['POST'])]
    public function __invoke(Request $request, ImageProcessorInterface $processor): Response
    {
        $upload = $request->files->get('image');
        if (!$upload instanceof UploadedFile) {
            return new Response('Select one image.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $asset = $processor->process($upload);

        // Persist $asset with the owning model before rendering it later.
        return $this->render('photo/show.html.twig', [
            'image' => $asset,
        ]);
    }
}
```

```twig
{# templates/photo/show.html.twig #}
{{ ux_picture(image, { alt: 'Uploaded photo' }) }}
```

The default profile generates JPEG widths at 640, 1024 and 1920 pixels. Move to
a named profile and named storage as soon as those choices become part of the
application's persistence contract.

The request crosses four visible boundaries:

1. `UploadedFile` is inspected from its real bytes and checked against the
   configured budgets.
2. `responsive_default` plans three proportional JPEG variants.
3. `default_public` stores the original and generated files, then `process()`
   returns one immutable `ImageAsset`.
4. `ux_picture()` renders `<picture>`, its format sources, the `<img>` fallback,
   `srcset`, `sizes` and intrinsic dimensions from that value.

Inspect the [real generated HTML](rendering.md#ux_picture-output) before adding
custom markup around it.

### Before persisting the first asset

- Name the profile after the layout contract (`avatar`, `product`, `hero`), not
  after an implementation detail.
- Name the storage at the `process()` call site.
- Decide whether originals may be public.
- Start with JPEG, then validate WebP/AVIF in the production PHP image.
- Persist the complete `ImageAsset`; never keep only the public URL.
- Define who removes replaced originals and variants when the owner is deleted.

## Storage setup

### Local storage

Local files are written under `ux_image.storage_root`, which defaults in the
bundle to the private path `%kernel.project_dir%/var/ux-image`. Configure
`default_public` and set `public_url_prefix` to the URL from which those files
are served:

```yaml
# config/packages/ux_image.yaml
ux_image:
    storages:
        avatars:
            public_url_prefix: /uploads/avatars
```

A storage that sets neither `flysystem_service` nor `adapter_service` is served by the built-in
local filesystem backend; `public_url_prefix` is the only key it needs.

When using the bundle default private root, serve files through a controller, a
symlink into `public/`, or a CDN. Change it with the `storage_root` configuration
key. See [Storage](storage.md). No additional packages are required for local
storage.

### Flysystem storage

Install the Flysystem bundle and the adapter for your cloud provider:

```bash
composer require league/flysystem-bundle
composer require league/flysystem-aws-s3-v3  # for S3
```

Configure the Flysystem filesystem service, then reference it:

```yaml
# config/packages/flysystem.yaml
flysystem:
    storages:
        product.storage:
            adapter: 'aws'
            options:
                client: 'aws_s3.client'
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

## Frontend assets

With `TwigBundle` active, rendering is entirely server-side:
`ux_picture()` and `ux_image()` output plain HTML (`<picture>`/`<img>`).
`<twig:ux:image>` is additionally available only when `TwigComponentBundle`
is active. No Stimulus controller, JavaScript, or CSS ships with UX Image, so
nothing needs to be added to your import map.

## Persistence and regeneration

Processing returns an `ImageAsset`; the application must persist that value
before rendering it later. Doctrine support is optional and explicit:

```yaml
# config/packages/ux_image.yaml
ux_image:
    doctrine_type: true
```

Always name the storage at the processing call site:

```php
$asset = $processor->process(
    $uploadedFile,
    profile: 'product',
    storage: 'product_images',
);
$product->setImage($asset);
$entityManager->flush();
```

If deferred or future profile regeneration is needed, configure exactly one
provider and one persister in `config/services.yaml`:

```yaml
services:
    App\Image\ProductImageProvider:
        tags: ['ux_image.regeneration.provider']

    App\Image\ProductImagePersister:
        tags: ['ux_image.regeneration.persister']
```

See [Regeneration](regeneration.md) for the typed batch and cursor contracts.

## Verify installation

```bash
php bin/console ux:image:validate
```

This command reports configuration warnings for the configured storages and profiles, then prints
a summary table of each. It exits non-zero when warnings are found.

The command writes, reads and deletes a temporary object under
`.ux-image-validation/` in every configured storage. Run it with credentials
that may perform all three operations.
