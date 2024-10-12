Symfony UX LazyImage
====================

Symfony UX LazyImage is a Symfony bundle providing utilities to improve
image loading performance. It is part of `the Symfony UX initiative`_.

It provides two key features:

-  a Stimulus controller to load lazily heavy images, with a placeholder
-  a `BlurHash implementation`_ to create data-uri thumbnails for images

Installation
------------

.. caution::

    Before you start, make sure you have `StimulusBundle configured in your app`_.

Install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-lazy-image

If you're using WebpackEncore, install your assets and restart Encore (not
needed if you're using AssetMapper):

.. code-block:: terminal

    $ npm install --force
    $ npm run watch

Usage
-----

The default usage of Symfony UX LazyImage is to use its Stimulus
controller to first load a small placeholder image that will then be
replaced by the high-definition version once the page has been rendered:

.. code-block:: html+twig

    <img
        src="{{ asset('image/small.png') }}"
        {{ stimulus_controller('symfony/ux-lazy-image/lazy-image', {
            src: asset('image/large.png')
        }) }}

        {# Optional but avoids having a page jump when the image is loaded #}
        width="200"
        height="150"
    >

With this setup, the user will initially see ``images/small.png``. Then,
once the page has loaded and the user’s browser has downloaded the
larger image, the ``src`` attribute will change to ``image/large.png``.

There is also support for the ``srcset`` attribute by passing an
``srcset`` value to the controller:

.. code-block:: html+twig

    <img
        src="{{ asset('image/small.png') }}"
        srcset="{{ asset('image/small.png') }} 1x, {{ asset('image/small2x.png') }} 2x"

        {{ stimulus_controller('symfony/ux-lazy-image/lazy-image', {
            src: asset('image/large.png'),
            srcset: {
                '1x': asset('image/large.png'),
                '2x': asset('image/large2x.png')
            }
        }) }}
    />

.. note::

    The ``stimulus_controller()`` function comes from `StimulusBundle`_.

Instead of using a generated thumbnail that would exist on your
filesystem, you can use the BlurHash algorithm to create a light,
blurred, data-uri thumbnail of the image:

.. code-block:: html+twig

    <img
        src="{{ data_uri_thumbnail('public/image/large.png', 100, 75) }}"
        {{ stimulus_controller('symfony/ux-lazy-image/lazy-image', {
            src: asset('image/large.png')
        }) }}

        {# Using BlurHash, the size is required #}
        width="200"
        height="150"
    />

The ``data_uri_thumbnail`` function receives 3 arguments:

-  the path to the image to generate the data-uri thumbnail for ;
-  the width of the BlurHash to generate
-  the height of the BlurHash to generate

Customizing images fetching
~~~~~~~~~~~~~~~~~~~~~~~~~~~

By default, ``data_uri_thumbnail`` fetches images using the `file_get_contents`_ function.
It works well for local files, but you may want to customize it to fetch images from a remote server, `Flysystem`_, etc.

To do so you can create a invokable class, the first argument is the filename to fetch::

    namespace App\BlurHash;

    class FetchImageContent
    {
        public function __invoke(string $filename): string
        {
            // Your custom implementation here to fetch the image content
        }
    }

Then you must configure the service in your Symfony configuration:

.. code-block:: yaml

    # config/packages/lazy_image.yaml
    lazy_image:
        fetch_image_content: 'App\BlurHash\FetchImageContent'

Performance considerations
~~~~~~~~~~~~~~~~~~~~~~~~~~

You should try to generate small BlurHash images as generating the image
can be CPU-intensive. Instead, you can rely on the browser scaling
abilities by generating a small image and using the ``width`` and
``height`` HTML attributes to scale up the image.

You can also configure a cache pool to store the generated BlurHash,
this way you can avoid generating the same BlurHash multiple times:

.. code-block:: yaml

    # config/packages/lazy_image.yaml
    framework:
        cache:
            pools:
                cache.lazy_image: cache.adapter.redis # or any other cache adapter depending on your needs

    lazy_image:
        cache: cache.lazy_image # the cache pool to use

Extend the default behavior
~~~~~~~~~~~~~~~~~~~~~~~~~~~

Symfony UX LazyImage allows you to extend its default behavior using a
custom Stimulus controller:

.. code-block:: javascript

    // mylazyimage_controller.js

    import { Controller } from '@hotwired/stimulus';

    export default class extends Controller {
        connect() {
            this.element.addEventListener('lazy-image:connect', this._onConnect);
            this.element.addEventListener('lazy-image:ready', this._onReady);
        }

        disconnect() {
            // You should always remove listeners when the controller is disconnected to avoid side-effects
            this.element.removeEventListener('lazy-image:connect', this._onConnect);
            this.element.removeEventListener('lazy-image:ready', this._onReady);
        }

        _onConnect(event) {
            // The lazy-image behavior just started
        }

        _onReady(event) {
            // The HD version has just been loaded
        }
    }

Then in your template, add your controller to the HTML attribute:

.. code-block:: html+twig

    <img
        src="{{ data_uri_thumbnail('public/image/large.png', 100, 75) }}"
        {{ stimulus_controller('mylazyimage')|stimulus_controller('symfony/ux-lazy-image/lazy-image', {
            src: asset('image/large.png')
        }) }}

        {# Using BlurHash, the size is required #}
        width="200"
        height="150"
    />

..

    **Note**: be careful to add your controller **before** the LazyImage
    controller so that it is executed before and can listen on the
    ``lazy-image:connect`` event properly.

Largest Contentful Paint (LCP) and Web performance considerations
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The `Largest Contentful Paint (LCP)`_ is a key metric for web performance.
It measures the time it takes for the largest image or text block to be rendered
on the page and should be less than 2.5 seconds. It's part of the `Core Web Vitals`_
and is used by Google to evaluate the user experience of a website, impacting
the Search ranking.

Using the Symfony UX LazyImage for your LCP image can be a good idea at first, 
but in reality, it will lower the LCP score because:

- `The progressive loading (through blurhash) is not taken into account in the LCP calculation`_;
- Even if you eagerly load the LazyImage Stimulus controller, a small delay will
  be added to the LCP calculation;
- If you `didn't preload the image`_, the browser will wait for the Stimulus
  controller to load the image, which adds another delay to the LCP calculation.

A solution is to not use the Stimulus controller for the LCP image but to use
``src`` and ``style`` attributes instead, and preload the image as well:

.. code-block:: html+twig

    <img
        src="{{ preload(asset('image/large.png'), { as: 'image', fetchpriority: 'high' }) }}"
        style="background-image: url('{{ data_uri_thumbnail('public/image/large.png', 20, 15) }}')"
        fetchpriority="high"

        {# Using BlurHash, the size is required #}
        width="200"
        height="150"
    />
    
This way, the browser will display the BlurHash image as soon as possible, and
will load the high-definition image at the same time, without waiting for the
Stimulus controller to be loaded.

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

.. _`the Symfony UX initiative`: https://ux.symfony.com/
.. _`BlurHash implementation`: https://blurha.sh
.. _`StimulusBundle`: https://symfony.com/bundles/StimulusBundle/current/index.html
.. _StimulusBundle configured in your app: https://symfony.com/bundles/StimulusBundle/current/index.html
.. _`file_get_contents`: https://www.php.net/manual/en/function.file-get-contents.php
.. _`Flysystem`: https://flysystem.thephpleague.com
.. _`Largest Contentful Paint (LCP)`: https://web.dev/lcp/
.. _`Core Web Vitals`: https://web.dev/vitals/
.. _`The progressive loading (through blurhash) is not taken into account in the LCP calculation`: https://github.com/w3c/largest-contentful-paint/issues/71_
.. _`didn't preload the image`: https://symfony.com/doc/current/web_link.html
