# Getting started

This kit provides ready-to-use and fully-customizable UI Twig components based on [daisyUI](https://daisyui.com/) components.

Please note that not every daisyUI component is available in this kit, but we are working on it!

## Requirements

This kit requires TailwindCSS v4 and daisyUI v5 to work:

### TailwindCSS

- If you use Symfony AssetMapper, you can install TailwindCSS with the [TailwindBundle](https://symfony.com/bundles/TailwindBundle/current/index.html),
- If you use Webpack Encore, you can follow the [TailwindCSS installation guide for Symfony](https://tailwindcss.com/docs/installation/framework-guides/symfony)

## Installation

daisyUI is a TailwindCSS plugin, not a stylesheet: TailwindCSS loads it with the `@plugin` directive when it builds your CSS.

### With npm (Webpack Encore)

1. Install daisyUI:

```shell
npm install daisyui@^5
```

2. Modify the file `assets/styles/app.css` with the following content:

```css
@import 'tailwindcss';

@plugin 'daisyui';
```

### With AssetMapper and the TailwindBundle

The TailwindBundle runs the standalone TailwindCSS executable. It can only load a plugin installed with npm when a `node_modules` directory exists, and an AssetMapper application usually has none.
daisyUI ships its plugin as a single JavaScript file for this case, see [Use daisyUI with Tailwind CSS Standalone CLI](https://daisyui.com/docs/install/standalone/).

1. Download the daisyUI plugin next to your `assets/styles/app.css` file:

```shell
curl -sLo assets/styles/daisyui.mjs https://github.com/saadeghi/daisyui/releases/download/v5.8.1/daisyui.mjs
```

2. Modify the file `assets/styles/app.css` with the following content:

```css
@import 'tailwindcss';

@source not './daisyui{,*}.mjs';

@plugin './daisyui.mjs';
```

Commit the `daisyui.mjs` file with your application. To update daisyUI, download it again from the URL of a newer v5 release.

## Themes

daisyUI uses the `light` theme by default, and the `dark` theme when the browser prefers a dark color scheme.
To choose a theme yourself, set the `data-theme` attribute on the `<html>` element:

```html
<html data-theme="dark">
```

See [daisyUI themes](https://daisyui.com/docs/themes/) to enable other themes or to create your own.
With AssetMapper, a custom theme needs the `daisyui-theme.mjs` file of the same release, for example https://github.com/saadeghi/daisyui/releases/download/v5.8.1/daisyui-theme.mjs.
