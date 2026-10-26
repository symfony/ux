# Symfony UX Image: Imagor

**EXPERIMENTAL** This component is currently experimental and is
likely to change, or even change drastically.

[Imagor](https://github.com/cshum/imagor) integration for Symfony UX Image, through transformation parameters in the image server's URL path.

## Installation

Install the bridge using Composer and Symfony Flex:

```shell
composer require symfony/ux-imagor-image
```

## DSN example

```dotenv
UX_IMAGE_DSN=imagor://images.example.com?origin=https%3A%2F%2Fwww.example.com
```

The host is the domain of your Imagor server. With `origin` pointing at your application's HTTP(S) base URL, `src` stays the path your application serves the image from. Without `origin`, `src` is a key for the server's configured loader, such as `bucket/uploads/photo.jpg` for S3. With `origin`, `src` always resolves under it, even when it is an absolute URL.

The DSN supports a port and an optional reverse-proxy path prefix, which the proxy must strip. HTTPS is the default; add `secure=false` for a local HTTP server:

```dotenv
UX_IMAGE_DSN=imagor://localhost:8000?secure=false
```

Pass filenames as unencoded paths and remote URLs as valid URLs, including any existing percent escapes. URL-encode DSN option values.

## Secure URLs

Imagor can require every URL to be signed with a [URL signature](https://docs.imagor.net/security/). Give the server's `IMAGOR_SECRET` as the `sign_key` DSN option:

```dotenv
UX_IMAGE_DSN=imagor://images.example.com?sign_key=your-secret
```

Every generated URL then carries a signature covering the encoded transformation and source path. The default `signer=sha1` and `sign_truncate=0` match stock Imagor. For a server using `IMAGOR_SIGNER_TYPE=sha256` and `IMAGOR_SIGNER_TRUNCATE=40`:

```dotenv
UX_IMAGE_DSN=imagor://images.example.com?sign_key=your-secret&signer=sha256&sign_truncate=40
```

`signer` also accepts `sha512`. `sign_truncate` accepts `0` (no truncation) or 17–88 characters. Without a signing key, URLs use `unsafe` and require `IMAGOR_UNSAFE=true` on the server, intended for local development.

> [!WARNING]
> The key signs every URL: anyone holding it can request transformations from your Imagor server. Keep it out of the files you commit, and store it the way you store your other secrets. Configure allowed sources on the server.

## Parameter mapping

`ImageTransformation` properties are mapped to Imagor's [image endpoint parameters](https://docs.imagor.net/image-endpoint/):

| `ImageTransformation` | Imagor parameter     | Notes                                          |
| --------------------- | -------------------- | ---------------------------------------------- |
| `width`               | `WIDTHxHEIGHT`       | An unspecified width becomes `0`               |
| `height`              | `WIDTHxHEIGHT`       | An unspecified height becomes `0`              |
| `fit: Fit::Cover`     | _(default)_          | Centered crop to the requested dimensions      |
| `fit: Fit::Contain`   | `fit-in`             | Fits within the dimensions without enlargement |
| `format`              | `format(NAME)`       | Explicit output format                         |
| `quality`             | `quality(N)`         | Output quality                                 |
| `operations`          | `filters:NAME(ARGS)` | See below                                      |

## Supported operations

Any of the following keys can be passed through `ImageTransformation::$operations` and are added as filters:

`background_color`, `blur`, `brightness`, `contrast`, `crop`, `dpi`, `fill`, `focal`, `grayscale`, `hue`, `no_upscale`, `page`, `rgb`, `rotate`, `round_corner`, `saturation`, `sharpen`, `strip_exif`, `strip_metadata`.

`grayscale`, `no_upscale`, `strip_exif` and `strip_metadata` take booleans; `false` omits the filter. Other operations take numbers or argument strings, such as `crop: '10,20,200,150'`. Arguments may contain letters, digits, commas, periods, colons, underscores and hyphens.

Filters retain insertion order, after the common quality and format filters. Each filter can appear once; `crop` runs after resizing. See [Imagor's filter reference](https://docs.imagor.net/filters/) for what each one does.

## Supported formats

`avif`, `webp`, `jpeg`, `png`.

Automatic format negotiation depends on Imagor's `IMAGOR_AUTO_*` server configuration, so the bridge does not advertise support for `format: 'auto'`. Use `ux_picture()` to offer browsers multiple explicit formats. If no format is specified, the server chooses its default.

## Resources

- [Documentation](https://symfony.com/bundles/ux-image/current/index.html)
- [Report issues](https://github.com/symfony/ux/issues) and
  [send Pull Requests](https://github.com/symfony/ux/pulls)
  in the [main Symfony UX repository](https://github.com/symfony/ux)
