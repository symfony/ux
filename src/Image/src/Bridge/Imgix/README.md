# Symfony UX Image: imgix

**EXPERIMENTAL** This component is currently experimental and is
likely to change, or even change drastically.

[imgix](https://www.imgix.com/) integration for Symfony UX Image, through query string parameters appended to your source's URL.

## Installation

Install the bridge using Composer and Symfony Flex:

```shell
composer require symfony/ux-imgix-image
```

## DSN example

```dotenv
UX_IMAGE_DSN=imgix://my-source.imgix.net
```

The host is the domain of your imgix source, either its `imgix.net` subdomain or a custom domain. With a [Web Folder source](https://docs.imgix.com/en-US/getting-started/setup/creating-sources/web-folder) pointing at your origin, `src` stays the path your application serves the image from. With a storage source, like Amazon S3, `src` is the path of the image in the bucket.

## Secure URLs

A source can require every URL to be signed, with [secure URLs](https://docs.imgix.com/en-US/getting-started/setup/securing-assets#enabling-secure-urls). Give the source's token as the `sign_key` DSN option:

```dotenv
UX_IMAGE_DSN=imgix://my-source.imgix.net?sign_key=the-token
```

Every generated URL then carries the matching `s` parameter, hashed over the path and the query string.

> [!WARNING]
> The token signs every URL: anyone holding it can request any transformation of any image of the source. Keep it out of the files you commit, and store it the way you store your other secrets.

## Parameter mapping

`ImageTransformation` properties are mapped to imgix's [rendering API parameters](https://docs.imgix.com/en-US/apis/rendering):

| `ImageTransformation` | imgix parameter | Notes                                                            |
| --------------------- | --------------- | ---------------------------------------------------------------- |
| `width`               | `w`             |                                                                  |
| `height`              | `h`             |                                                                  |
| `fit: Fit::Cover`     | `fit=crop`      |                                                                  |
| `fit: Fit::Contain`   | `fit=clip`      |                                                                  |
| `format`              | `fm`            | `format: 'auto'` becomes `auto=format`, imgix picks AVIF or WebP |
| `quality`             | `q`             |                                                                  |
| `operations`          | _(as given)_    | merged in verbatim, see below                                    |

## Supported operations

Any of the following keys can be passed through `ImageTransformation::$operations` and are forwarded as-is as query parameters:

`auto`, `bg`, `blur`, `border`, `bri`, `con`, `crop`, `dpr`, `exp`, `fill`, `fill-color`, `flip`, `fp-x`, `fp-y`, `fp-z`, `gam`, `high`, `invert`, `monochrome`, `orient`, `pad`, `rect`, `rot`, `sat`, `sepia`, `shad`, `sharp`, `trim`, `usm`, `vib`.

An `auto` operation, like `compress`, is joined to the `auto=format` of automatic format negotiation. See the [rendering API reference](https://docs.imgix.com/en-US/apis/rendering) for what each one does.

## Supported formats

`avif`, `webp`, `jpeg`, `png`.

## Resources

- [Documentation](https://symfony.com/bundles/ux-image/current/index.html)
- [Report issues](https://github.com/symfony/ux/issues) and
  [send Pull Requests](https://github.com/symfony/ux/pulls)
  in the [main Symfony UX repository](https://github.com/symfony/ux)
