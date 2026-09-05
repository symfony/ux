# Symfony UX Image: Glide

**EXPERIMENTAL** This component is currently experimental and is
likely to change, or even change drastically.

[Glide](https://glide.thephpleague.com/) integration for Symfony UX Image, through a controller served by your own application. This is the local, no-CDN provider: images are resized and encoded on-the-fly by your own server, from a source directory you control, with results cached to disk. Use this bridge when you don't want to depend on a third-party CDN or image service.

## Installation

Install the bridge using Composer and Symfony Flex:

```shell
composer require symfony/ux-glide-image
```

## DSN example

```dotenv
# .env
UX_IMAGE_DSN=glide://default/images?source=%kernel.project_dir%/public/uploads&cache=%kernel.project_dir%/var/glide-cache&sign_key=s3cret
```

```yaml
# config/packages/ux_image.yaml
ux_image:
    provider: '%env(resolve:UX_IMAGE_DSN)%'
```

The `resolve:` processor is required: without it Glide receives the literal `%kernel.project_dir%/public/uploads` as its source directory, since parameter resolution does not recurse into environment variable values on its own.

The host (`default` above) is always a placeholder: Glide has no remote endpoint to point at, only a local source and cache. What matters is:

| DSN part                    | Meaning                                                                                         |
| --------------------------- | ----------------------------------------------------------------------------------------------- |
| path (`/images`)            | the URL prefix images are served under, e.g. `/images/hero.jpg?w=800`                           |
| `source`                    | absolute path to the directory holding your original images                                     |
| `cache`                     | absolute path to the directory Glide writes resized/encoded images to                           |
| `sign_key` (optional)       | secret used to sign generated URLs; when set, `s=<signature>` is required on every request      |
| `max_image_size` (optional) | output pixel cap per image, `25000000` by default; Glide scales an oversized request down to it |

Setting a `sign_key` is **strongly recommended in production**. Without one the route resizes and caches whatever anyone asks it for, and every distinct parameter combination costs one encode and one new cache file. `max_image_size` bounds how large a single output can get, but only a signature stops the request from being served at all.

## Wiring the route

This bridge ships a controller but not a route with a fixed prefix — the prefix must match the DSN's path exactly, so your application supplies it:

```yaml
# config/routes/ux_image_glide.yaml
ux_image_glide:
    resource: '@UXImageBundle/config/routes/glide.php'
    prefix: /images
```

The `prefix` here and the path segment of `UX_IMAGE_DSN` (`/images` in the example above) must be the same string. If they drift apart, `ux_image()` generates URLs your route can't match.

Importing this route without `symfony/ux-glide-image` installed fails with a plain "class not found" error, which is expected: the route references `GlideController` by its fully-qualified class name.

## Parameter mapping

`ImageTransformation` properties are mapped to [Glide's own query parameters](https://glide.thephpleague.com/4.0/api/quick-reference/):

| `ImageTransformation` | Glide parameter | Notes                                                                                                                                                                  |
| --------------------- | --------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `width`               | `w`             |                                                                                                                                                                        |
| `height`              | `h`             |                                                                                                                                                                        |
| `fit: Fit::Cover`     | `fit=crop`      |                                                                                                                                                                        |
| `fit: Fit::Contain`   | `fit=contain`   |                                                                                                                                                                        |
| `fit: Fit::ScaleDown` | `fit=max`       |                                                                                                                                                                        |
| `format`              | `fm`            | `format: 'auto'` is resolved by `GlideController` from `Accept`, since Glide itself has no `auto`; `jpeg` is accepted and translated to `fm=jpg`, Glide's own spelling |
| `quality`             | `q`             |                                                                                                                                                                        |
| `operations`          | _(as given)_    | merged in verbatim, see below                                                                                                                                          |

## Automatic format negotiation

Unlike Cloudflare, Glide's `fm` parameter only accepts concrete formats. `GlideController` reads the request's `Accept` header, replaces `fm=auto` with the first format both it and the browser support (falling back to `jpg` otherwise, regardless of `getSupportedFormats()`'s content or order), and sets `Vary: Accept` on the response so caches don't mix up results for different browsers. The candidates it picks from are the application's own `ux_image.formats`, intersected with the list below. This is why `supportsAutoFormat()` returns `true` for this provider: it's the controller doing the negotiation, not Glide.

## Supported operations

Any of the following keys can be passed through `ImageTransformation::$operations` and are forwarded as-is to Glide:

`crop`, `or`, `bri`, `con`, `gam`, `sharp`, `blur`, `pixel`, `filt`, `bg`, `border`.

See the [full API reference](https://glide.thephpleague.com/4.0/api/quick-reference/) for what each one does.

## Supported formats

`avif`, `webp`, `jpeg`, `pjpg`, `png`, `gif`, `heic`.

## Resources

- [Documentation](https://symfony.com/bundles/ux-image/current/index.html)
- [Report issues](https://github.com/symfony/ux/issues) and
  [send Pull Requests](https://github.com/symfony/ux/pulls)
  in the [main Symfony UX repository](https://github.com/symfony/ux)
