# Symfony UX Image: IPX

**EXPERIMENTAL** This component is currently experimental and is
likely to change, or even change drastically.

[IPX](https://github.com/unjs/ipx) integration for Symfony UX Image. IPX is a self-hosted image server built on [sharp](https://sharp.pixelplumbing.com/). It reads the transformation from the URL path, `/<modifiers>/<image path>`.

## Installation

Install the bridge using Composer and Symfony Flex:

```shell
composer require symfony/ux-ipx-image
```

## DSN example

```dotenv
UX_IMAGE_DSN=ipx://images.example.com
```

The DSN uses HTTPS by default. The port and the path are optional, like `ipx://images.example.com:8443/_ipx`. Add `secure=false` to switch to plain HTTP.

## Running IPX

IPX runs from the official `node:26-alpine` Docker image. You configure it with environment variables. This example reads the images from your application's `public/` directory:

```yaml
# compose.yaml
services:
    ipx:
        image: node:26-alpine
        command: npx --yes ipx@4 serve
        environment:
            IPX_FS_DIR: /srv/public
            IPX_ALLOWED_MODIFIERS: width,height,resize,fit,format,quality
        volumes:
            - ./public:/srv/public:ro
```

> [!WARNING]
> IPX has no URL signing, so anyone can request any transformation it accepts. In `IPX_ALLOWED_MODIFIERS`, list only what you use. `width`, `height`, `resize`, `fit`, `format` and `quality` cover the common parameters. Add every extra operation you pass.

### Local development

Publish the IPX port in `compose.override.yaml`. Docker Compose reads this file on top of `compose.yaml`:

```yaml
# compose.override.yaml
services:
    ipx:
        ports: [3000]
```

The Symfony CLI sets `IPX_HOST` and `IPX_PORT` from the published port. IPX serves plain HTTP, so the DSN needs `secure=false`:

```dotenv
# .env.dev
UX_IMAGE_DSN=ipx://${IPX_HOST}:${IPX_PORT}?secure=false
```

### Production

IPX listens on port 3000. Put it behind the reverse proxy that serves the host of the DSN. `compose.prod.yaml` publishes IPX on a fixed port, on `127.0.0.1` only, so a reverse proxy on the same machine can reach it and the outside cannot:

```yaml
# compose.prod.yaml
services:
    ipx:
        ports: ['127.0.0.1:3000:3000']
```

In production, start the services with both files, and do not use `compose.override.yaml`:

```shell
docker compose -f compose.yaml -f compose.prod.yaml up -d
```

IPX does not store transformed images. It transforms the image again on every request, so cache its responses in the reverse proxy or a CDN.

By default, IPX sends `Cache-Control: max-age=60`. To use a longer duration, set `IPX_FS_MAX_AGE` in seconds, like `86400`.

## Parameter mapping

`ImageTransformation` properties are mapped to IPX [modifiers](https://github.com/unjs/ipx#modifiers):

| `ImageTransformation` | IPX modifier         | Notes                                                       |
| --------------------- | -------------------- | ----------------------------------------------------------- |
| `width`               | `w`                  |                                                             |
| `height`              | `h`                  |                                                             |
| `width` and `height`  | `s_<width>x<height>` | IPX reads `fit` only with `s`                               |
| `fit: Fit::Cover`     | `fit_cover`          |                                                             |
| `fit: Fit::Contain`   | `fit_inside`         | `fit_contain` would pad the image to the requested box      |
| `format`              | `f`                  | `format: 'auto'` becomes `f_auto`, negotiated from `Accept` |
| `quality`             | `q`                  |                                                             |
| `operations`          | _(as given)_         | merged in verbatim, see below                               |

## Supported operations

You can pass any of these keys through `ImageTransformation::$operations`. They are forwarded as-is as modifiers:

`animated`, `autoorient`, `background`, `blur`, `brightness`, `clahe`, `dilate`, `enlarge`, `erode`, `extend`, `extract`, `flatten`, `flip`, `flop`, `gamma`, `grayscale`, `hue`, `kernel`, `lightness`, `linear`, `median`, `modulate`, `negate`, `normalize`, `opacity`, `position`, `rotate`, `saturation`, `sharpen`, `threshold`, `tint`, `trim`, `unflatten`.

Join the arguments of an operation with `_`, like `extract: '0_0_800_450'`. Pass `true` for an operation without arguments, like `grayscale`. See the [modifier reference](https://github.com/unjs/ipx#modifiers) for what each one does.

## Supported formats

`avif`, `webp`, `jpeg`, `png`.

## Resources

- [Documentation](https://symfony.com/bundles/ux-image/current/index.html)
- [Report issues](https://github.com/symfony/ux/issues) and
  [send Pull Requests](https://github.com/symfony/ux/pulls)
  in the [main Symfony UX repository](https://github.com/symfony/ux)
