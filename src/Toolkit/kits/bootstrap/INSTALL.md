# Getting started

This kit provides ready-to-use and fully-customizable UI Twig components based on [Bootstrap](https://getbootstrap.com/) components's **design**.

Please note that not every Bootstrap component is available in this kit, but we are working on it!

## Requirements

This kit requires Bootstrap 5.3+ to work.

## Installation

1. Install Bootstrap, either with `importmap:require` for AssetMapper, or `npm` for Webpack Encore:

```
# With AssetMapper
php bin/console importmap:require bootstrap/dist/css/bootstrap.min.css bootstrap

# With npm
npm install bootstrap
```

2. Import Bootstrap CSS in your `assets/styles/app.css`:

```css
@import 'bootstrap/dist/css/bootstrap.min.css';
```

Or if using Webpack Encore, import it in your `assets/app.js`:

```js
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';
```
