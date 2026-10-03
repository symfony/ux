# Symfony UX Image: Cloudinary

**EXPERIMENTAL** This component is currently experimental and is
likely to change, or even change drastically.

[Cloudinary](https://cloudinary.com/documentation/image_transformations) integration for Symfony UX Image. Use this bridge to transform images either from your own origin or from your Cloudinary media library, without running any image-processing server yourself.

## Installation

Install the bridge using Composer and Symfony Flex:

```shell
composer require symfony/ux-cloudinary-image
```

## DSN example

```dotenv
UX_IMAGE_DSN=cloudinary://my-cloud?origin=https://example.com
```

The host is your Cloudinary cloud name. The `origin` option picks how images are delivered:

- With `origin`, the provider uses [fetch delivery](https://cloudinary.com/documentation/fetch_remote_images). `origin` is the base URL your application serves the original images from, and every `src` is appended to it. Cloudinary downloads each original from there, then transforms and caches it. No upload to Cloudinary is needed.
- Without `origin` (`cloudinary://my-cloud`), the provider uses upload delivery, and `src` is the public ID of an image in your media library, without its leading slash. To keep the same `src` as your application, set up an [auto-upload mapping](https://cloudinary.com/documentation/migration#lazy_migration_with_auto_upload) from a folder to your origin: Cloudinary then copies each original into the media library on the first request.

## Signed URLs

An account can require every delivery URL to be signed, with [Strict transformations](https://cloudinary.com/documentation/control_access_to_media#strict_transformations). Give the account's API secret as the `api_secret` DSN option:

```dotenv
UX_IMAGE_DSN=cloudinary://my-cloud?origin=https://example.com&api_secret=the-api-secret
```

Every generated URL then carries the matching `s--…--` signature, hashed over the transformation and the source URL or public ID.

> [!WARNING]
> The API secret signs every URL, and it also grants full access to your Cloudinary account through its API. Keep it out of the files you commit, and store it the way you store your other secrets.

## Parameter mapping

`ImageTransformation` properties are mapped to Cloudinary's [transformation parameters](https://cloudinary.com/documentation/transformation_reference):

| `ImageTransformation` | Cloudinary parameter | Notes                                                                |
| --------------------- | -------------------- | -------------------------------------------------------------------- |
| `width`               | `w`                  |                                                                      |
| `height`              | `h`                  |                                                                      |
| `fit: Fit::Cover`     | `c_fill`             |                                                                      |
| `fit: Fit::Contain`   | `c_fit`              |                                                                      |
| `format`              | `f`                  | `format: 'auto'` lets Cloudinary pick AVIF/WebP based on the request |
| `quality`             | `q`                  |                                                                      |
| `operations`          | _(as given)_         | merged in verbatim, see below                                        |

## Supported operations

Any of the following keys can be passed through `ImageTransformation::$operations` and are forwarded as-is to the Cloudinary URL:

`a`, `b`, `bo`, `co`, `d`, `dpr`, `e`, `fl`, `g`, `o`, `r`, `t`, `x`, `y`, `z`.

A value can contain a `:`, like `e: 'sharpen:100'`, but not a `/` or a `,`: Cloudinary reads those as the start of a new transformation or parameter. See the [transformation reference](https://cloudinary.com/documentation/transformation_reference) for what each one does.

## Supported formats

`avif`, `webp`, `jpeg`, `png`.

Cloudinary also supports `f_auto`, so this provider negotiates the best format for the requesting browser itself: the single `<img>` of `ux_image()` still gets a modern format. `ux_picture()` names each format explicitly instead.

## Resources

- [Documentation](https://symfony.com/bundles/ux-image/current/index.html)
- [Report issues](https://github.com/symfony/ux/issues) and
  [send Pull Requests](https://github.com/symfony/ux/pulls)
  in the [main Symfony UX repository](https://github.com/symfony/ux)
