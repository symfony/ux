# Symfony UX Swup

Symfony UX Swup is a Symfony bundle integrating [Swup](https://swup.js.org/) in
Symfony applications. It is part of [the Symfony UX initiative](https://symfony.com/ux).

Swup is a complete and easy to use page transition library for Web applications. It creates
a Single Page Application feel to Web applications without having to change anything on the server
and without bringing the complexity of a React/Vue/Angular application.

## Installation

Symfony UX Swup does not use any PHP and can be installed with any PHP/Symfony version.

You can install this bundle using Composer and Symfony Flex:

```sh
composer require symfony/ux-swup

# Don't forget to install the JavaScript dependencies as well and compile
yarn install --force
yarn encore dev
```

Also make sure you have at least version 3.0 of [@symfony/stimulus-bridge](https://github.com/symfony/stimulus-bridge)
in your `package.json` file.

## Usage

In order to implement page transitions, Swup works by transforming the links of your
application in AJAX calls to the target in their href. Once the AJAX call result is
received, Swup is able to swap the content of the current page with the new content
received by AJAX. When doing this swap, it is therefore able to animate a transition
between pages.

The main usage of Symfony UX Swup is to use its Stimulus controller to initialize Swup:

```twig
<html lang="en">
    <head>
        <title>Swup</title>

        {% block javascripts %}
            {{ encore_entry_script_tags('app') }}
        {% endblock %}
    </head>
    <body {{ stimulus_controller('symfony/ux-swup/swup') }}>
        {# ... #}

        <main id="swup">
            {# ... #}
        </main>
    </body>
</html>
```

**Note** The `stimulus_controller()` function comes from
[WebpackEncoreBundle v1.10](https://github.com/symfony/webpack-encore-bundle).

That's it! Swup now reacts to a link click and run the default fade-in transition.

By default, Swup will use the `#swup` selector as a container, meaning it will only swap
the content of this container from one page to another. If you wish, you can configure
additional containers, for instance to have a navigation menu that updates when changing pages:

```twig
<html lang="en">
    <head>
        <title>Swup</title>

        {% block javascripts %}
            {{ encore_entry_script_tags('app') }}
        {% endblock %}
    </head>
    <body
        {{ stimulus_controller('symfony/ux-swup/swup', {
            containers: ['#swup', '#nav']
        }) }}
    >
        {# ... #}

        <nav id="nav">
            {# ... #}
        </nav>

        <main id="swup">
            {# ... #}
        </main>
    </body>
</html>
```

You can configure several other options using values on the controller.
Most of these correspond to [Swup Options](https://swup.js.org/options),
but there are a few extra added:

```twig
<html lang="en">
    <head>
        <title>Swup</title>
    </head>
    <body
        {{ stimulus_controller('symfony/ux-swup/swup', {
            containers: ['#swup', '#nav'],
            animateHistoryBrowsing: true
            animationSelector: '[class*="transition-"]',
            cache: true,
            linkSelector: '...',

            theme: 'slide',
            debug: true,
        }) }}
    >
        {# ... #}
    </body>
</html>
```

The extra options are:

-   `theme`: either `slide` or `fade` (the default);
-   `debug`: add this attribute to enable debug.

### Extend the default behavior

Symfony UX Swup allows you to extend its default behavior using a custom Stimulus controller:

```js
// assets/controllers/myswup_controller.js

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.element.addEventListener('swup:pre-connect', this._onPreConnect);
        this.element.addEventListener('swup:connect', this._onConnect);
    }

    disconnect() {
        // You should always remove listeners when the controller is disconnected to avoid side-effects
        this.element.removeEventListener('swup:pre-connect', this._onConnect);
        this.element.removeEventListener('swup:connect', this._onPreConnect);
    }

    _onPreConnect(event) {
        // Swup has not been initialized - options can be changed
        console.log(event.detail.options); // Options that will be used to initialize Swup
    }

    _onConnect(event) {
        // Swup has just been intialized and you can access details from the event
        console.log(event.detail.swup); // Swup instance
        console.log(event.detail.options); // Options used to initialize Swup
    }
}
```

Then in your template, add your controller to the HTML attribute:

```twig
<html lang="en">
    <head>
        <title>Swup</title>
        {# ... #}
    </head>
    <body {{ stimulus_controller({
        myswup: {},
        'symfony/ux-swup/swup': {}
    }) }}>
        {# ... #}
    </body>
</html>
```

> **Note**: be careful to add your controller **before** the Swup controller so that
> it is executed before and can listen on the `swup:connect` event properly.

## Backward Compatibility promise

This bundle aims at following the same Backward Compatibility promise as the Symfony framework:
[https://symfony.com/doc/current/contributing/code/bc.html](https://symfony.com/doc/current/contributing/code/bc.html)

However it is currently considered
[**experimental**](https://symfony.com/doc/current/contributing/code/experimental.html),
meaning it is not bound to Symfony's BC policy for the moment.

## Run tests

### PHP tests

```sh
php vendor/bin/phpunit
```

### JavaScript tests

```sh
cd Resources/assets
yarn test
```
