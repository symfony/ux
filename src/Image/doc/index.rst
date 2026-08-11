Symfony UX Image
================

**EXPERIMENTAL** This component is currently experimental and is likely
to change, or even change drastically.

Symfony UX Image provides Twig components for rendering ``<img>``,
``<picture>`` and ``<source>`` HTML elements. It validates attributes
against the HTML specification at render time and supports responsive images,
format selection, art direction and lazy loading.

Installation
------------

.. code-block:: terminal

    $ composer require symfony/ux-image

If you are using ``symfony/ux-twig-component``, the Twig components
``<twig:ux:img>``, ``<twig:ux:picture>`` and ``<twig:ux:source>`` are
registered automatically.

Usage
-----

Basic image
~~~~~~~~~~~

Use the ``<twig:ux:img>`` component to render an ``<img>`` element:

.. code-block:: html+twig

    <twig:ux:img
        src="/photos/hero.jpg"
        alt="A beautiful landscape"
        width="1200"
        height="800"
        loading="lazy"
        decoding="async"
    />

The ``alt`` attribute is always required. Use an empty string for
decorative images (``alt=""``), but never omit it.

Any extra attribute (``class``, ``id``, ``data-*``, etc.) is passed
through to the rendered ``<img>`` element:

.. code-block:: html+twig

    <twig:ux:img
        src="/photos/hero.jpg"
        alt="Hero"
        class="rounded shadow"
        data-lightbox="gallery"
    />

Responsive images with srcset
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Pass ``srcset`` and ``sizes`` as PHP arrays for a clean, readable syntax.
The component normalizes them to the correct HTML format:

.. code-block:: html+twig

    <twig:ux:img
        src="/photos/hero-800.jpg"
        alt="Responsive hero image"
        :srcset="[
            '/photos/hero-480.jpg 480w',
            '/photos/hero-800.jpg 800w',
            '/photos/hero-1200.jpg 1200w',
        ]"
        :sizes="[
            '(max-width: 600px) 480px',
            '(max-width: 1000px) 800px',
            '1200px',
        ]"
        loading="lazy"
    />

You can also pass ``srcset`` and ``sizes`` as plain strings:

.. code-block:: html+twig

    <twig:ux:img
        src="/photos/hero-800.jpg"
        alt="Responsive hero image"
        srcset="/photos/hero-480.jpg 480w, /photos/hero-800.jpg 800w, /photos/hero-1200.jpg 1200w"
        sizes="(max-width: 600px) 480px, (max-width: 1000px) 800px, 1200px"
    />

Pixel density descriptors (``1x``, ``2x``) are supported for fixed-size
images like logos or avatars:

.. code-block:: html+twig

    <twig:ux:img
        src="/logo.png"
        alt="Logo"
        :srcset="['/logo.png 1x', '/logo@2x.png 2x']"
    />

.. caution::

    You cannot mix width descriptors (``w``) and density descriptors (``x``)
    in the same ``srcset``.

Format selection with picture
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Use ``<twig:ux:picture>`` with ``<twig:ux:source>`` to serve modern image
formats with automatic fallback. The browser picks the first format it
supports:

.. code-block:: html+twig

    <twig:ux:picture>
        <twig:ux:source
            srcset="/photos/landscape.avif"
            type="image/avif"
        />
        <twig:ux:source
            srcset="/photos/landscape.webp"
            type="image/webp"
        />
        <twig:ux:img
            src="/photos/landscape.jpg"
            alt="Mountain landscape"
            width="800"
            height="600"
        />
    </twig:ux:picture>

Browsers that support AVIF download the smallest file, those that only
support WebP get the WebP version, and all others fall back to JPEG.

Art direction
~~~~~~~~~~~~~

Use the ``media`` attribute on ``<twig:ux:source>`` to serve different
images depending on viewport size — for example, a wide landscape on
desktop and a cropped portrait on mobile:

.. code-block:: html+twig

    <twig:ux:picture class="hero">
        <twig:ux:source
            srcset="/photos/hero-desktop.webp"
            media="(min-width: 800px)"
            type="image/webp"
        />
        <twig:ux:source
            srcset="/photos/hero-mobile.webp"
            type="image/webp"
        />
        <twig:ux:img
            src="/photos/hero-mobile.jpg"
            alt="Hero image"
            width="400"
            height="300"
        />
    </twig:ux:picture>

Art direction and format selection can be combined by adding both
``media`` and ``type`` on the same ``<twig:ux:source>``.

Responsive sources with srcset
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

``<twig:ux:source>`` also accepts ``srcset`` and ``sizes`` for responsive
resolution switching inside a ``<picture>``:

.. code-block:: html+twig

    <twig:ux:picture>
        <twig:ux:source
            :srcset="[
                '/photos/hero-480.avif 480w',
                '/photos/hero-800.avif 800w',
                '/photos/hero-1200.avif 1200w',
            ]"
            sizes="(max-width: 600px) 480px, 800px"
            type="image/avif"
        />
        <twig:ux:img
            src="/photos/hero-800.jpg"
            alt="Hero"
            width="800"
            height="600"
        />
    </twig:ux:picture>

Lazy loading and sizes="auto"
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Use ``loading="lazy"`` to defer loading of off-screen images. Do **not**
lazy-load the largest above-the-fold image (LCP) — use ``fetchpriority="high"``
instead:

.. code-block:: html+twig

    {# Below-the-fold image: lazy load #}
    <twig:ux:img src="/photos/footer.jpg" alt="Footer" loading="lazy" />

    {# LCP image: prioritize #}
    <twig:ux:img src="/photos/hero.jpg" alt="Hero" fetchpriority="high" />

The ``sizes="auto"`` keyword lets the browser compute the display size
automatically. It requires ``loading="lazy"``:

.. code-block:: html+twig

    <twig:ux:img
        src="/photos/hero.jpg"
        alt="Hero"
        :srcset="['/photos/hero-480.jpg 480w', '/photos/hero-800.jpg 800w']"
        sizes="auto"
        loading="lazy"
    />

You can provide a fallback value for browsers that don't support
``auto``: ``sizes="auto, 100vw"``.

Twig functions
~~~~~~~~~~~~~~

If you prefer a programmatic approach, two Twig functions are available:

.. code-block:: html+twig

    {# Render an <img> #}
    {{ ux_img(
        src: '/photos/hero.jpg',
        alt: 'Hero image',
        width: 1200,
        height: 800,
        loading: 'lazy',
    ) }}

    {# Render a <picture> with format fallbacks #}
    {{ ux_picture(
        sources: [
            { srcset: '/photos/hero.avif', type: 'image/avif' },
            { srcset: '/photos/hero.webp', type: 'image/webp' },
        ],
        src: '/photos/hero.jpg',
        alt: 'Hero image',
        width: 1200,
        height: 800,
    ) }}

Supported attributes
--------------------

``<twig:ux:img>``
~~~~~~~~~~~~~~~~~

========================= ================== ===========================================
Attribute                 Type               Description
========================= ================== ===========================================
``src``                   ``string``         Image URL (required if no ``srcset``)
``alt``                   ``string``         Alternative text (**always required**)
``srcset``                ``string|array``   Image candidates with descriptors
``sizes``                 ``string|array``   Display size hints for the browser
``width``                 ``int``            Intrinsic width in pixels
``height``                ``int``            Intrinsic height in pixels
``loading``               ``string``         ``lazy`` or ``eager``
``decoding``              ``string``         ``sync``, ``async`` or ``auto``
``fetchpriority``         ``string``         ``high``, ``low`` or ``auto``
``crossorigin``           ``string``         ``anonymous`` or ``use-credentials``
``referrerpolicy``        ``string``         Referrer policy value
``ismap``                 ``bool``           Server-side image map
``usemap``                ``string``         Client-side image map reference
========================= ================== ===========================================

Any additional attribute (``class``, ``id``, ``data-*``, ``style``, …) is
passed through to the rendered ``<img>`` element.

``<twig:ux:source>``
~~~~~~~~~~~~~~~~~~~~

========================= ================== ===========================================
Attribute                 Type               Description
========================= ================== ===========================================
``srcset``                ``string|array``   Image candidates (**required**)
``sizes``                 ``string|array``   Display size hints
``media``                 ``string``         Media query for art direction
``type``                  ``string``         MIME type (e.g. ``image/avif``)
``width``                 ``int``            Intrinsic width in pixels
``height``                ``int``            Intrinsic height in pixels
========================= ================== ===========================================

.. note::

    The ``src`` attribute is **not** allowed on ``<source>`` — use ``srcset``.

``<twig:ux:picture>``
~~~~~~~~~~~~~~~~~~~~~

The ``<picture>`` element accepts any global HTML attribute (``class``,
``id``, etc.) and wraps ``<twig:ux:source>`` and ``<twig:ux:img>`` children.

Validation
----------

The components validate attributes against the HTML specification
at render time. Invalid usage throws an ``\InvalidArgumentException`` with a
clear error message:

``alt`` is required
    Use ``alt=""`` for decorative images. Omitting ``alt`` entirely throws
    an error.

``src`` or ``srcset`` required
    At least one of them must be provided on ``<twig:ux:img>``.

No mixing ``w`` and ``x`` descriptors
    A ``srcset`` must use either width descriptors (``480w``) or pixel density
    descriptors (``2x``), never both.

No duplicate descriptors
    Each descriptor value must appear only once in a ``srcset``.

``sizes`` requires width descriptors
    When ``sizes`` is present, all ``srcset`` candidates must use ``w``
    descriptors.

``sizes="auto"`` requires ``loading="lazy"``
    The ``auto`` keyword (or ``auto, <fallback>``) is only valid with lazy
    loading.

Enum attributes validated
    ``loading``, ``decoding``, ``fetchpriority``, ``crossorigin`` and
    ``referrerpolicy`` only accept their spec-defined values.

No ``src`` on ``<source>``
    The ``<source>`` element only accepts ``srcset``, not ``src``.

Backward compatibility promise
------------------------------

This bundle follows the same backward-compatibility promise as
`Symfony itself <https://symfony.com/doc/current/contributing/code/bc.html>`_.
