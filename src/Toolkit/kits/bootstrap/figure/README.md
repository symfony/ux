# Figure

Display related visual content with an optional caption and responsive alignment.

```twig {"preview":true}
<twig:Figure caption="Bukchon Hanok Village, in Seoul, South Korea" captionAlign="center" style="max-width: 36rem;">
    <img
        src="https://images.unsplash.com/photo-1535189043414-47a3c49a0bed?auto=format&fit=crop&w=960&q=80"
        class="figure-img img-fluid rounded"
        alt="Bukchon Hanok Village, in Seoul, South Korea"
    >
</twig:Figure>
```

## Installation

::: installation

## Usage

```twig
<twig:Figure caption="Bukchon Hanok Village, in Seoul, South Korea" style="max-width: 36rem;">
    <img
        src="https://images.unsplash.com/photo-1535189043414-47a3c49a0bed?auto=format&fit=crop&w=720&q=80"
        class="figure-img img-fluid rounded"
        alt="Bukchon Hanok Village, in Seoul, South Korea"
    >
</twig:Figure>
```

## Accessibility

Write image alternative text that describes the image's purpose in context. Avoid repeating the caption verbatim unless both carry distinct information.

Use an empty `alt` value for a decorative image. The caption remains associated with the figure through the native `figure` and `figcaption` elements.

## Examples

### Default figure

Display a responsive image with a caption below it.

```twig {"preview":true}
<twig:Figure caption="Bukchon Hanok Village, in Seoul, South Korea" style="max-width: 36rem;">
    <img
        src="https://images.unsplash.com/photo-1535189043414-47a3c49a0bed?auto=format&fit=crop&w=720&q=80"
        class="figure-img img-fluid rounded"
        alt="Bukchon Hanok Village, in Seoul, South Korea"
    >
</twig:Figure>
```

### Caption alignment

Align the caption with Bootstrap's text utilities through the `captionAlign` prop.

```twig {"preview":true}
<twig:Figure caption="Bukchon Hanok Village, in Seoul, South Korea" captionAlign="end" style="max-width: 36rem;">
    <img
        src="https://images.unsplash.com/photo-1535189043414-47a3c49a0bed?auto=format&fit=crop&w=720&q=80"
        class="figure-img img-fluid rounded"
        alt="Bukchon Hanok Village, in Seoul, South Korea"
    >
</twig:Figure>
```

## API Reference

::: api-reference
