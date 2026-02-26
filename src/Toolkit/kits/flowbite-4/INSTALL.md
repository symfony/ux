# Getting started

This kit provides ready-to-use and fully-customizable UI Twig components based on [Flowbite](https://flowbite.com/) components's **design**.

Please note that not every Flowbite component is available in this kit, but we are working on it!

## Requirements

This kit requires TailwindCSS and Flowbite v4 to work:

### TailwindCSS

- If you use Symfony AssetMapper, you can install TailwindCSS with the [TailwindBundle](https://symfony.com/bundles/TailwindBundle/current/index.html),
- If you use Webpack Encore, you can follow the [TailwindCSS installation guide for Symfony](https://tailwindcss.com/docs/installation/framework-guides/symfony)

### Flowbite

- Install flowbite via npm — this creates the node_modules/flowbite
```
npm install flowbite --save
```

## Installation

Add flowbite

```
php bin/console importmap:require flowbite
```

Append to the file `assets/app.js` the following content:

```js
import 'flowbite';
```

Modify the file `assets/styles/app.css` with the following content:

```css
@import 'tailwindcss';

@plugin "flowbite/plugin";
@import "flowbite/src/themes/default";
@source "../../node_modules/flowbite";

@custom-variant dark (&:is(.dark *));

/* You can customize theming here see https://flowbite.com/docs/customize/theming/ */
```
