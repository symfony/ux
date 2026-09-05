# Symfony UX Image: Cloudflare

**EXPERIMENTAL** This component is currently experimental and is
likely to change, or even change drastically.

[Cloudflare Image Resizing](https://developers.cloudflare.com/images/transform-images/) integration for Symfony UX Image, through the `/cdn-cgi/image/` URL path. Use this bridge to transform images already served from your own origin ("bring your own storage") behind a Cloudflare zone, without running any image-processing server yourself.

> [!IMPORTANT]
> Image transformations must be enabled on your Cloudflare zone before `/cdn-cgi/image/` URLs work. See [Enable transformations](https://developers.cloudflare.com/images/transform-images/#enable-transformations-via-dashboard) in the Cloudflare docs.

## Installation

Install the bridge using Composer and Symfony Flex:

```shell
composer require symfony/ux-cloudflare-image
```

## DSN example

```dotenv
UX_IMAGE_DSN=cloudflare://cdn.example.com
```

The host is the domain proxied by your Cloudflare zone; it must be the domain the origin images are served from.

## Parameter mapping

`ImageTransformation` properties are mapped to Cloudflare's [`options` URL segment](https://developers.cloudflare.com/images/transform-images/transform-via-url/#options):

| `ImageTransformation` | Cloudflare option | Notes                                                                |
| --------------------- | ----------------- | -------------------------------------------------------------------- |
| `width`               | `width`           |                                                                      |
| `height`              | `height`          |                                                                      |
| `fit: Fit::Cover`     | `fit=cover`       |                                                                      |
| `fit: Fit::Contain`   | `fit=contain`     |                                                                      |
| `fit: Fit::ScaleDown` | `fit=scale-down`  |                                                                      |
| `format`              | `format`          | `format: 'auto'` lets Cloudflare pick AVIF/WebP based on the request |
| `quality`             | `quality`         |                                                                      |
| `operations`          | _(as given)_      | merged in verbatim, see below                                        |

## Supported operations

Any of the following keys can be passed through `ImageTransformation::$operations` and are forwarded as-is to the Cloudflare URL:

`gravity`, `dpr`, `rotate`, `trim`, `blur`, `brightness`, `contrast`, `gamma`, `saturation`, `sharpen`, `background`, `border`, `anim`, `metadata`, `onerror`, `compression`.

See the [full options reference](https://developers.cloudflare.com/images/transform-images/transform-via-url/#options) for what each one does.

## Supported formats

`avif`, `webp`, `jpeg`, `png`.

Cloudflare also supports `format=auto`, so this provider negotiates the best format for the requesting browser itself; `ux_image()` renders a single `<img>` rather than a `<picture>` with per-format `<source>` elements.

## Resources

- [Documentation](https://symfony.com/bundles/ux-image/current/index.html)
- [Report issues](https://github.com/symfony/ux/issues) and
  [send Pull Requests](https://github.com/symfony/ux/pulls)
  in the [main Symfony UX repository](https://github.com/symfony/ux)
