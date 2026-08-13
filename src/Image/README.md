# Symfony UX Image

Symfony UX Image turns an uploaded raster image into a persistable
`ImageAsset`, then renders native responsive HTML from that metadata.

```text
PHP                         Twig                      Browser
UploadedFile + profile  →   ImageAsset + options  →  <picture>, srcset, sizes
process once · persist      render without I/O       select natively · no JS
```

It is designed for server-rendered Symfony applications. Your application
keeps ownership of forms, uploads, entities and cleanup; UX Image owns the
processing profile, stored outputs and rendering contract.

## Install

```bash
composer require symfony/ux-image
```

Symfony Flex configures local public storage. The default driver requires
`ext-gd`; PHP 8.4+ and Symfony 7.4 or 8.x are supported.
Flex also registers `TwigBundle` and `UXImageBundle`. Without Flex, register
both bundles before using the Twig functions.

## PHP: process once and persist

Declare the output widths required by the layout:

```yaml
# config/packages/ux_image.yaml
ux_image:
    profiles:
        product:
            directory: products
            formats: [jpeg]
            sizes: '(min-width: 64rem) 50vw, 100vw'
            variants:
                small:  { width: 480, mode: fit }
                medium: { width: 960, mode: fit }
                large:  { width: 1440, mode: fit, quality: 88 }
```

Process the real `UploadedFile` when it enters the application. The returned
`ImageAsset` is immutable, versioned metadata that can be stored with the
owning model:

```php
use Symfony\UX\Image\Processor\ImageProcessorInterface;

$asset = $processor->process(
    $uploadedFile,
    profile: 'product',
    storage: 'default_public',
);

$product->setImage($asset);
$entityManager->flush();
```

The processor inspects the input, applies the named profile and writes the
original plus its variants before returning the asset.

## Twig: render metadata without I/O

Pass the persisted value directly to Twig:

```twig
{{ ux_picture(product.image, {
    alt: product.name,
    lazy: false,
    fetchpriority: 'high',
}) }}
```

`ux_picture()` and `ux_image()` turn `ImageAsset` metadata into HTML. Rendering
does not read the files or contact storage.

## Browser: select natively

The generated `<picture>` or `<img>` contains standard `srcset`, `sizes`,
intrinsic `width` and `height`, and loading hints. The browser selects the
appropriate candidate for its viewport and pixel density. When candidates
preserve the declared ratio, intrinsic dimensions reserve layout space before
download. UX Image does not require JavaScript.
Turbo is optional. Stimulus can enhance interactions around the image, but it
is not part of this rendering pipeline.

## Scope

UX Image provides named processing profiles, local or Flysystem storage,
persistable `ImageAsset` values, Twig rendering and regeneration hooks. It does
not provide a file picker, upload transport, entity ownership or an image
editor; compose those concerns with Symfony Forms, UX Upload or application
code.

Start with the [documentation overview](doc/overview.md), then see
[processing](doc/processing.md), [rendering](doc/rendering.md),
[persistence](doc/image-asset.md) and [storage](doc/storage.md).

## Contributing

This repository is a read-only subtree split. Report issues and send pull
requests in the [Symfony UX repository](https://github.com/symfony/ux).

Last reviewed: July 2026.
