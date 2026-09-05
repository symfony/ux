# Symfony UX Image: KeyCDN

**EXPERIMENTAL** This component is currently experimental and is
likely to change, or even change drastically.

[KeyCDN Image Processing](https://www.keycdn.com/support/image-processing) integration for Symfony UX Image, through query string parameters appended to your zone's URL. Use this bridge to transform images already served from your own origin ("bring your own storage") behind a KeyCDN zone, without running any image-processing server yourself.

## Installation

Install the bridge using Composer and Symfony Flex:

```shell
composer require symfony/ux-keycdn-image
```

## DSN example

```dotenv
UX_IMAGE_DSN=keycdn://myzone.kxcdn.com
```

The host is your KeyCDN zone.

## Parameter mapping

`ImageTransformation` properties are mapped to KeyCDN's [image processing query parameters](https://www.keycdn.com/support/image-processing):

| `ImageTransformation` | KeyCDN parameter | Notes                         |
| --------------------- | ---------------- | ----------------------------- |
| `width`               | `width`          |                               |
| `height`              | `height`         |                               |
| `fit: Fit::Cover`     | `fit=cover`      |                               |
| `fit: Fit::Contain`   | `fit=contain`    |                               |
| `fit: Fit::ScaleDown` | `fit=inside`     |                               |
| `format`              | `format`         |                               |
| `quality`             | `quality`        |                               |
| `operations`          | _(as given)_     | merged in verbatim, see below |

## Supported operations

Any of the following keys can be passed through `ImageTransformation::$operations` and are forwarded as-is as query parameters:

`position`, `enlarge`, `trim`, `crop`, `bg`, `rotate`, `flip`, `flop`, `sharpen`, `blur`, `gamma`, `grayscale`, `progressive`, `lossless`, `metadata`.

See the [full parameter reference](https://www.keycdn.com/support/image-processing) for what each one does.

## Supported formats

`webp`, `jpeg`, `png`.

Unlike Cloudflare, KeyCDN has no `format=auto` equivalent: there is no automatic format negotiation, and AVIF is not among the formats it can encode to. Because of this, `ux_image()` renders a `<picture>` element with one `<source type>` per configured format instead of a single `<img>`.

## Resources

- [Documentation](https://symfony.com/bundles/ux-image/current/index.html)
- [Report issues](https://github.com/symfony/ux/issues) and
  [send Pull Requests](https://github.com/symfony/ux/pulls)
  in the [main Symfony UX repository](https://github.com/symfony/ux)
