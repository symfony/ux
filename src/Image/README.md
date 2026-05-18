# Symfony UX Image

Optimized responsive image components with automatic format conversion and Core Web Vitals optimization.

## Installation

```bash
composer require symfony/ux-image
```

## Quick Start

### Basic Usage

```twig
{# Simple responsive image #}
<twig:ux:img
    src="/images/hero.jpg"
    alt="Hero image"
    width="100vw md:80vw"
/>
```

### Art Direction

```twig
{# Different aspect ratios per breakpoint #}
<twig:ux:picture
    src="/images/banner.jpg"
    alt="Banner"
    width="100vw md:80vw"
    ratio="sm:1:1 md:16:9"
/>
```

### Without Twig Components

```twig
{{ ux_image('/images/hero.jpg', 'Hero image', { width: '100vw md:80vw' }) }}
```

## Features

- **Automatic WebP conversion** — via configurable providers
- **Responsive srcset/sizes** — viewport-based width syntax (`100vw md:80vw`)
- **Density support** — Retina displays (`densities="x1 x2"`)
- **Art direction** — different crops per breakpoint with `<twig:ux:picture>`
- **Provider-based** — works with local files, CDN, or any image service
- **No hard dependencies** — no image processing library required

## Configuration

```yaml
# config/packages/ux_image.yaml
ux_image:
    default_provider: passthrough
    breakpoints:
        sm: 640
        md: 768
        lg: 1024
        xl: 1280
        2xl: 1536
    defaults:
        format: webp
        quality: 80
        loading: lazy
        fit: cover
```

### Using a CDN

```yaml
ux_image:
    providers:
        cloudflare:
            pattern: '/cdn-cgi/image/w={width},f={format},q={quality}/{src}'
```

## License

MIT License — see [LICENSE](LICENSE) for details.
