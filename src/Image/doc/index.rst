Symfony UX Image
================

Symfony UX Image is a Symfony bundle providing optimized responsive image
components with automatic format conversion, smart cropping, and Core Web
Vitals optimization.

The bundle provides two Twig components:

* ``<twig:img>`` - For simple responsive images with automatic WebP conversion
* ``<twig:picture>`` - For art direction with different crops per breakpoint

Installation
------------

.. code-block:: terminal

    $ composer require symfony/ux-image

If you're using Symfony Flex, the bundle will be automatically enabled. Otherwise,
enable it manually in your ``config/bundles.php`` file:

.. code-block:: php

    return [
        // ...
        Symfony\UX\Image\ImageBundle::class => ['all' => true],
    ];

Components
----------

Img Component
~~~~~~~~~~~~~

The ``<twig:img>`` component generates optimized responsive images with
automatic srcset generation:

.. code-block:: html+twig

    {# Simple responsive image #}
    <twig:img
        src="/images/hero.jpg"
        alt="Hero image"
        width="100vw sm:50vw"
    />

    {# With aspect ratio and focal point #}
    <twig:img
        src="/images/hero.jpg"
        alt="Hero image"
        width="100vw sm:50vw"
        ratio="16:9"
        focal="center"
    />

    {# Optimized hero image #}
    <twig:img
        src="/images/hero.jpg"
        alt="Hero image"
        width="100vw"
        preload="true"
        fetchpriority="high"
    />

Available Attributes
^^^^^^^^^^^^^^^^^^^^

Required Attributes
"""""""""""""""""""

``src``
    Path to the source image

``alt``
    Alternative text for accessibility

Common Attributes
"""""""""""""""""

``width``
    Responsive widths using breakpoint syntax (e.g., ``100vw sm:50vw md:400px``)

``densities``
    Pixel density variants to generate (e.g., ``x1 x2``)

``ratio``
    Aspect ratio for cropping (e.g., ``16:9``, ``4:3``, ``1:1``)

``preset``
    Name of predefined configuration preset

Optimization Attributes
"""""""""""""""""""""""

``preload``
    Set to ``true`` to add preload link for critical images

``fetchpriority``
    Set to ``high`` for LCP images, ``low`` for below-the-fold images

``loading``
    Set to ``lazy`` for lazy loading (default), or ``eager`` for immediate loading

Image Processing Attributes
"""""""""""""""""""""""""""

``format``
    Output format (``webp``, ``jpg``, ``png``). Default: ``webp``

``quality``
    Image quality from 0-100. Default: ``80``

``focal``
    Focus point for smart cropping: ``center``, ``top``, ``bottom``, ``left``, ``right``, or coordinates like ``0.5,0.3``

``fit``
    How image should fit dimensions: ``cover`` (default), ``contain``, ``fill``, ``inside``, ``outside``

``fallback``
    Fallback breakpoint for browsers without srcset support. Default: ``lg``

``fallback-format``
    Format for fallback image. Default: ``auto``

``background``
    Background color for ``contain`` fit mode (e.g., ``#ffffff``)

Picture Component
~~~~~~~~~~~~~~~~~

The ``<twig:picture>`` component provides art direction capabilities with
different image crops for different viewport sizes:

.. code-block:: html+twig

    {# Different aspect ratios per breakpoint #}
    <twig:picture
        src="/images/hero.jpg"
        alt="Hero image"
        width="100vw md:80vw"
        ratio="sm:1:1 md:16:9"
    />

Configuration
-------------

Preloading Images
~~~~~~~~~~~~~~~~~

To preload critical images for better LCP (Largest Contentful Paint), simply
mark them with the ``preload`` attribute:

.. code-block:: html+twig

    <twig:img
        src="/images/hero.jpg"
        alt="Hero"
        width="100vw"
        preload="true"
        fetchpriority="high"
    />

The bundle automatically injects preload ``<link>`` tags into the ``<head>``
section via an event subscriber. No additional configuration needed!

**Best Practices:**

* Only preload your LCP (Largest Contentful Paint) image - typically 1-2 images per page
* Combine with ``fetchpriority="high"`` for maximum effect
* Don't preload too many images - it can slow down the initial page load

The generated HTML will include:

.. code-block:: html

    <head>
        <meta charset="UTF-8" />
        <title>My Page</title>
        <!-- Automatically injected by PreloadInjectorSubscriber -->
        <link rel="preload" as="image" href="..." imagesrcset="..." imagesizes="..." />
    </head>
    <body>
        <img src="..." srcset="..." sizes="..." fetchpriority="high" alt="Hero" />
    </body>

.. note::

   For responsive images, the bundle uses ``imagesrcset`` and ``imagesizes``
   attributes on the ``<link>`` tag, which mirror the ``srcset`` and ``sizes``
   attributes on the ``<img>`` tag. This allows browsers to preload the
   appropriate image variant based on viewport size and pixel density.

Default Configuration
~~~~~~~~~~~~~~~~~~~~~

Create a configuration file at ``config/packages/ux_image.yaml``:

.. code-block:: yaml

    ux_image:
        provider: 'liip_imagine'

        missing_image_placeholder: '/images/image-not-found.png'

        defaults:
            format: 'webp'
            quality: 80
            fallback: 'lg'
            fallback_format: 'auto'

        breakpoints:
            sm: 640
            md: 768
            lg: 1024
            xl: 1280
            '2xl': 1536

Using Presets
~~~~~~~~~~~~~

Define reusable image configurations:

.. code-block:: yaml

    ux_image:
        presets:
            hero:
                width: '100vw'
                ratio: '16:9'
                quality: 85
                focal: 'center'

            thumbnail:
                width: '300'
                ratio: '1:1'
                fit: 'cover'
                quality: 75

            avatar:
                width: '128'
                ratio: '1:1'
                fit: 'cover'
                format: 'webp'

Use presets in your templates:

.. code-block:: html+twig

    <twig:img src="/images/hero.jpg" alt="Hero" preset="hero" />

    <twig:img src="/images/profile.jpg" alt="Profile" preset="avatar" />

Responsive Widths
~~~~~~~~~~~~~~~~~

Width Syntax
^^^^^^^^^^^^

Define different widths for different breakpoints:

.. code-block:: html+twig

    {# Full width on mobile, half width on tablet and up #}
    <twig:img src="/image.jpg" alt="" width="100vw md:50vw" />

    {# Fixed width on mobile, viewport width on desktop #}
    <twig:img src="/image.jpg" alt="" width="300 lg:100vw" />

    {# Viewport width until large, then fixed #}
    <twig:img src="/image.jpg" alt="" width="100vw lg:800" />

Generated Image Versions
^^^^^^^^^^^^^^^^^^^^^^^^

The bundle automatically generates appropriate image sizes based on your
width configuration:

==================== =====================================
Width String         Generated Versions
==================== =====================================
``100``              100px
``1000``             1000px
``sm:50 md:100``     50px, 100px, 200px
``100vw``            640px, 768px, 1024px, 1280px, 1536px
``50vw lg:400px``    320px, 384px, 400px
``100 lg:100vw``     100px, 1024px, 1280px, 1536px
==================== =====================================

Fit Options
~~~~~~~~~~~

Control how images are resized and cropped:

``cover`` (default)
    Crop to fill, maintaining aspect ratio

.. code-block:: html+twig

    <twig:img src="/image.jpg" alt="" width="400" ratio="16:9" fit="cover" />

``contain``
    Fit within dimensions, maintaining aspect ratio (may add letterboxing)

.. code-block:: html+twig

    <twig:img
        src="/image.jpg"
        alt=""
        width="400"
        ratio="16:9"
        fit="contain"
        background="#f0f0f0"
    />

``fill``
    Stretch to fill dimensions (may distort)

``inside``
    Resize to fit within dimensions

``outside``
    Resize to cover dimensions

Focal Points
~~~~~~~~~~~~

Control which part of the image stays in view when cropping:

Named Positions
^^^^^^^^^^^^^^^

.. code-block:: html+twig

    <twig:img src="/image.jpg" alt="" width="400" ratio="1:1" focal="center" />
    <twig:img src="/image.jpg" alt="" width="400" ratio="1:1" focal="top" />
    <twig:img src="/image.jpg" alt="" width="400" ratio="1:1" focal="left" />

Coordinate Positions
^^^^^^^^^^^^^^^^^^^^

Use normalized coordinates (0.0 to 1.0) for precise control:

.. code-block:: html+twig

    {# Focus on point 50% from left, 30% from top #}
    <twig:img
        src="/image.jpg"
        alt=""
        width="400"
        ratio="16:9"
        fit="cover"
        focal="0.5,0.3"
    />

Providers
---------

The bundle supports multiple image providers for different deployment
scenarios.

LiipImagine Provider
~~~~~~~~~~~~~~~~~~~~

Local image processing using LiipImagineBundle:

.. code-block:: yaml

    ux_image:
        provider: 'liip_imagine'
        providers:
            liip_imagine:
                enabled: true

Cloudinary Provider
~~~~~~~~~~~~~~~~~~~

Cloud-based image processing:

.. code-block:: yaml

    ux_image:
        provider: 'cloudinary'
        providers:
            cloudinary:
                enabled: true
                cloud_name: 'your-cloud-name'
                base_url: 'https://res.cloudinary.com/your-cloud-name/image/upload'

Fastly Provider
~~~~~~~~~~~~~~~

CDN-based image optimization:

.. code-block:: yaml

    ux_image:
        provider: 'fastly'
        providers:
            fastly:
                enabled: true
                base_url: 'https://www.fastly.io'

Placeholder Provider
~~~~~~~~~~~~~~~~~~~~

For development and testing:

.. code-block:: yaml

    ux_image:
        provider: 'placeholder'
        providers:
            placeholder:
                enabled: true
                base_url: 'https://via.placeholder.com'

Common Use Cases
----------------

Hero Images
~~~~~~~~~~~

Large, full-width hero images optimized for Core Web Vitals:

.. code-block:: html+twig

    <twig:img
        src="/images/hero.jpg"
        alt="Welcome to our site"
        width="100vw"
        ratio="16:9"
        preload="true"
        fetchpriority="high"
        focal="center"
        quality="85"
    />

Thumbnails
~~~~~~~~~~

Fixed-size thumbnails with smart cropping:

.. code-block:: html+twig

    <twig:img
        src="/images/product.jpg"
        alt="Product name"
        width="300"
        ratio="1:1"
        fit="cover"
        focal="center"
        loading="lazy"
    />

Avatar Images
~~~~~~~~~~~~~

User profile images:

.. code-block:: html+twig

    <twig:img
        src="/images/user-avatar.jpg"
        alt="User name"
        width="128"
        ratio="1:1"
        fit="cover"
        format="webp"
        class="rounded-full"
    />

Responsive Gallery
~~~~~~~~~~~~~~~~~~

Image gallery with different layouts per viewport:

.. code-block:: html+twig

    <twig:img
        src="/images/gallery-1.jpg"
        alt="Gallery image"
        width="100vw sm:50vw lg:33vw"
        ratio="4:3"
        loading="lazy"
    />

Art Direction
~~~~~~~~~~~~~

Use ``<twig:picture>`` when you need different aspect ratios for different
viewport sizes. The ratio values cascade across breakpoints (like CSS):

.. code-block:: html+twig

    <twig:picture
        src="/images/banner.jpg"
        alt="Banner"
        width="100vw md:80vw"
        ratio="sm:1:1 md:16:9"
    />

This generates exclusive media query ranges to ensure the browser selects
the correct aspect ratio:

* **sm (640px-767px)**: Square images (1:1 ratio)
* **md and above (768px+)**: Widescreen images (16:9 ratio)

.. note::

    Breakpoint-specific ``focal`` and ``fit`` attributes are coming soon!

Error Handling
--------------

Missing Images
~~~~~~~~~~~~~~

Configure a placeholder for missing images:

.. code-block:: yaml

    ux_image:
        missing_image_placeholder: '/images/image-not-found.png'

Invalid Images
~~~~~~~~~~~~~~

The bundle will throw an exception for invalid image paths in development
mode. In production, it will use the configured placeholder.

Security
--------

The bundle includes built-in security features:

* Image paths are validated and sanitized
* Only configured directories are accessible
* Image dimensions are limited to prevent abuse
* File type validation prevents malicious uploads

Backward Compatibility Promise
-------------------------------

This bundle follows `Symfony's Backward Compatibility Promise`_.

.. _Symfony's Backward Compatibility Promise: https://symfony.com/doc/current/contributing/code/bc.html

