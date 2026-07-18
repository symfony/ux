# Getting started

This kit provides ready-to-use Twig components based on [Bootstrap 5.3](https://getbootstrap.com/docs/5.3/).

## Requirements

Bootstrap's CSS is required by all components. Bootstrap's JavaScript is also required by interactive components and examples.

## Installation

Install Bootstrap with AssetMapper:

```bash
php bin/console importmap:require bootstrap bootstrap/dist/css/bootstrap.min.css
```

Then import Bootstrap from `assets/app.js`:

```js
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';
```

With npm, pnpm, or Yarn, install Bootstrap instead with your package manager:

```bash
npm install bootstrap@^5.3.0
```

The imports in `assets/app.js` remain the same.

And that's it! You can now use the Bootstrap Twig components in your templates.
