Symfony UX Image
================

**EXPERIMENTAL** This component is currently experimental and is likely
to change, or even change drastically.

Symfony UX Image renders responsive images in Symfony applications by
delegating every transformation to a URL-based image provider, such as
`Cloudflare`_ or `KeyCDN`_. It is part of `the Symfony UX initiative`_.

The package never decodes, resizes or encodes an image itself. Every
transformation is expressed as a URL, built by whichever provider is
active; the package's own job is generating that URL, a ``srcset``, a
``sizes`` attribute and a layout ``style``.

Installation
------------

Install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-image

Then install one of the provider bridges, for example `Cloudflare`_:

.. code-block:: terminal

    $ composer require symfony/ux-cloudflare-image

The Twig functions and the ``<twig:ux:image>`` and ``<twig:ux:picture>``
components need ``symfony/ux-twig-component``. Without it, the bundle still
provides the ``ImageUrlGenerator`` service (see
:ref:`Getting a single URL <image_single_url>`), which is enough for an API or
a worker:

.. code-block:: terminal

    $ composer require symfony/ux-twig-component

Rendering an image
------------------

The ``<twig:ux:image>`` component renders a single image:

.. code-block:: html+twig

    <twig:ux:image src="/uploads/hero.jpg" alt="Hero" width="800" height="450" />

``src`` is the public URL path of the original image, as your application
serves it. Every provider reads it the same way: Cloudflare and KeyCDN fetch it
from your origin, and ``null://`` renders it as is. Switching providers
therefore never changes your templates.

The equivalent ``ux_image()`` Twig function is available for programmatic use,
for example when the source path is only known inside a Twig macro:

.. code-block:: twig

    {{ ux_image('/uploads/hero.jpg', 'Hero', {width: 800, height: 450}) }}

Both accept the same rendering options: the component exposes them as props,
the function takes them as its third, associative-array argument, keyed by
prop name in camelCase (``objectFit``, not ``object-fit``). An unknown key
there throws an ``InvalidArgumentException``.

Both accept HTML attributes too: the component takes them next to its props,
the function as its fourth argument:

.. code-block:: twig

    {{ ux_image('/uploads/hero.jpg', 'Hero', {width: 800}, {class: 'rounded', loading: 'eager'}) }}

Props
~~~~~

=============== ================================= ================================
Prop            Type                              Default
=============== ================================= ================================
``src``         ``string``                        required
``alt``         ``string``                        required
``layout``      ``fixed|constrained|full-width``  ``constrained``
``width``       ``int|null``                      ``null``
``height``      ``int|null``                      ``null``
``fit``         ``cover|contain|null``            ``cover`` or ``null``, see below
``format``      ``string|null``                   ``null``
``quality``     ``int|null`` (1 to 100)           ``null``
``priority``    ``bool``                          ``false``
``object-fit``  ``string|null``                   the ``fit`` value
``breakpoints`` ``int[]|null``                    the ``resolutions`` option
``operations``  ``array<string, array>``          ``{}``
=============== ================================= ================================

``layout`` and its ``breakpoints``, ``sizes`` and generated ``style`` are
covered under :ref:`Layout and rendering <image_layout_and_rendering>`.
``priority`` sets ``loading="eager" fetchpriority="high"``; without it, an
image only gets ``loading="lazy"``.

``fit`` decides how the provider reshapes the source image into the requested
``width`` x ``height``. ``cover`` fills the box and crops the excess, and
``contain`` fits the whole image inside it. Both enlarge a source that is
smaller than the box. ``fit`` defaults to ``cover`` when both ``width`` and
``height`` are set, and it only has an effect then, since without both there
is no target box to fit into. It only produces a visible difference when the
source image's aspect ratio differs from the requested one: at equal ratios
there is nothing to crop and nothing to letterbox, so both modes come out
identical.

The generated ``object-fit`` follows ``fit`` (``cover`` -> ``cover``,
``contain`` -> ``contain``), so the browser doesn't redo a crop the provider
was asked to avoid. Without that, a ``contain`` image would be cropped back to
fill its box by CSS, and ``fit`` would never be observable.

``format`` pins the output format for this one image, in place of both the
provider's own negotiation and the per-format ``<picture>`` fallbacks: the
``<img>`` is rendered in that format, and a ``<picture>`` gets no ``<source>``. A format the active provider
cannot produce throws an ``InvalidArgumentException`` naming its supported
list.

Any attribute not listed above (``class``, ``data-*``, a caller-supplied
``style`` or ``sizes``, …), like any attribute passed to ``ux_image()``, is
passed through to the rendered ``<img>`` and wins over a generated one such as
``loading``. A caller-supplied ``style`` merges with the generated layout style
instead of replacing it; a caller-supplied ``sizes`` replaces the generated
value.

.. _image_provider_operations:

Provider-specific operations
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Every provider accepts extra, provider-specific parameters beyond the common
``width``/``height``/``fit``/``format``/``quality`` set (see
:ref:`Providers <image_providers>` for the full list per bridge). Pass them
through ``operations``, keyed by provider name:

.. code-block:: html+twig

    <twig:ux:image src="/uploads/hero.jpg" alt="Hero" width="800"
        :operations="{cloudflare: {sharpen: 1}, keycdn: {sharpen: 10}}" />

At render time, only ``operations[activeProviderName]`` is read; the rest is
ignored. Keying by provider name is deliberate: the active DSN changes
between environments, and a flat, un-keyed ``gravity`` option would silently
vanish the moment the application switched from Cloudflare to another
provider. Passing an operation the active provider does not support throws
an ``InvalidArgumentException`` naming the provider's supported list. A key
that names no installed provider throws as well, so a typo such as
``cloudfare`` fails in development rather than being silently ignored in
production.

.. _image_single_url:

Getting a single URL
~~~~~~~~~~~~~~~~~~~~

Some places need one URL rather than a responsive image: an Open Graph image,
an email, an API response or a CSS background. The ``ux_image_url()`` Twig
function returns it:

.. code-block:: html+twig

    <meta property="og:image" content="{{ absolute_url(ux_image_url('/uploads/hero.jpg', {width: 1200, height: 630})) }}">

It accepts ``width``, ``height``, ``fit``, ``format``, ``quality`` and
``operations``, with the same meaning as the component's props. Like the
component, it defaults ``fit`` to ``cover`` when both ``width`` and ``height``
are set. An unknown option throws an ``InvalidArgumentException``.

In PHP, autowire ``Symfony\UX\Image\ImageUrlGenerator``, which applies the
same validation::

    use Symfony\UX\Image\ImageUrlGenerator;

    public function __construct(
        private ImageUrlGenerator $imageUrlGenerator,
    ) {
    }

    public function ogImage(): string
    {
        return $this->imageUrlGenerator->generate('/uploads/hero.jpg', width: 1200, height: 630);
    }

Configuration
-------------

Configuration is done in your ``config/packages/ux_image.yaml`` file:

.. code-block:: yaml

    # config/packages/ux_image.yaml
    ux_image:
        provider: '%env(resolve:UX_IMAGE_DSN)%'
        formats: ['avif', 'webp', 'jpeg']
        resolutions: [6016, 5120, 4480, 3840, 3200, 2560, 2048, 1920, 1668, 1280, 1080, 960, 828, 750, 640]
        quality: null

The ``resolve:`` processor is required: a DSN may reference container
parameters such as ``%kernel.project_dir%``, and parameter resolution does not
recurse into environment variable values on its own.

The provider DSN
~~~~~~~~~~~~~~~~

The ``provider`` option is a DSN string that selects which bridge renders
your images, and configures it. Define it in your ``.env`` files so it can
change per environment:

.. code-block:: bash

    # .env
    UX_IMAGE_DSN=null://null

.. code-block:: bash

    # .env.prod
    UX_IMAGE_DSN=cloudflare://cdn.example.com

``null://null`` is the default when no provider is configured. It needs no
bridge and transforms nothing: each image renders with its original URL, no
``srcset`` and no ``sizes``, but keeps its dimensions and layout ``style``.
That makes it the provider to develop and test templates with when no CDN is
available.

Each bridge is only registered when its Composer package is actually
installed. Install the one matching the scheme used in the DSN:

============== ================================================ ===========================================
Scheme         Install                                          DSN example
============== ================================================ ===========================================
``keycdn``     ``composer require symfony/ux-keycdn-image``     ``keycdn://myzone.kxcdn.com``
``cloudflare`` ``composer require symfony/ux-cloudflare-image`` ``cloudflare://cdn.example.com``
============== ================================================ ===========================================

See :ref:`Providers <image_providers>` for what each DSN option means and how
transformation parameters map to that provider's own query string. A DSN
option the provider does not support throws an ``InvalidArgumentException``
naming the options it does support.

The ``formats`` option
~~~~~~~~~~~~~~~~~~~~~~

``formats`` is the candidate output format list, in preference order. It is
intersected with the active provider's own supported formats (see
:ref:`Providers <image_providers>`), then:

* ``ux_picture()`` renders one ``<source>`` per surviving entry (see
  :ref:`Layout and rendering <image_layout_and_rendering>`);
* ``ux_image()`` renders its single ``<img>`` in the last surviving entry,
  unless the provider negotiates the format itself, like `Cloudflare`_ with
  its own ``format=auto``; ``formats`` then has no effect on it.

An empty intersection throws an exception naming both lists, so asking a
provider that cannot encode AVIF to serve only AVIF fails at first render
rather than serving the wrong format silently.

The default is ``['avif', 'webp', 'jpeg']``. Narrowing it is how an
application keeps a format off the wire.

The ``resolutions`` option
~~~~~~~~~~~~~~~~~~~~~~~~~~

``resolutions`` is the resolution ladder the ``constrained`` and
``full-width`` layouts draw their ``srcset`` candidates from (see
:ref:`The resolution ladder <image_resolution_ladder>`). It defaults to
``Symfony\UX\Image\Renderer\LayoutResolver::DEFAULT_RESOLUTIONS``, the common
screen widths ported from `unpic`_.

Narrow it when your images never reach those sizes, or when you want fewer
candidates per ``srcset``; a component can still override it for one image
through the ``breakpoints`` prop. Each candidate is one more variant the
provider generates and caches, so a shorter ladder also means fewer
transformations on your CDN.

The ``quality`` option
~~~~~~~~~~~~~~~~~~~~~~

``quality`` is the output quality, from 1 to 100, of every generated image
that does not set its own through the ``quality`` prop or option. It defaults
to ``null``, which leaves the quality to the provider.

.. _image_layout_and_rendering:

Layout and rendering
--------------------

The ``layout`` prop
~~~~~~~~~~~~~~~~~~~

``layout`` decides the breakpoint ladder used to build ``srcset``, the
``sizes`` attribute, and the generated ``style``. ``constrained`` is the
default.

``fixed``
    Breakpoints: ``[width, 2 × width]``. ``sizes``: ``{width}px``. ``style``:
    ``object-fit: cover; width: {width}px; height: {height}px``.

``constrained`` (the default)
    Breakpoints: ``[width, 2 × width, …ladder entries below 2 × width]``.
    ``sizes``: ``(min-width: {width}px) {width}px, 100vw``. ``style``:
    ``object-fit: cover; max-width: {width}px; max-height: {height}px;
    aspect-ratio: {width/height}; width: 100%; height: auto``.

``full-width``
    Breakpoints: the full resolution ladder. ``sizes``: ``100vw``. With both
    ``width`` and ``height`` given, ``style``: ``object-fit: cover; width:
    100%; aspect-ratio: {width/height}; height: auto``. With only ``height``
    (no derivable ratio), ``style``: ``object-fit: cover; width: 100%;
    height: {height}px``.

Both ``constrained`` and ``full-width`` (when a ratio applies) declare
``height: auto`` alongside ``aspect-ratio``: the ``width``/``height`` HTML
attributes on ``<img>`` become a definite CSS height through the browser's
own presentational hint, which would otherwise make CSS ignore
``aspect-ratio`` outright.

``fixed`` and ``constrained`` require ``width``; ``full-width`` requires
``height``. Passing neither throws an ``InvalidArgumentException``.

``object-fit`` overrides the value derived from ``fit``. It defaults to the
``fit`` value itself, or to ``cover`` when no ``fit`` applies.

.. _image_resolution_ladder:

The resolution ladder
~~~~~~~~~~~~~~~~~~~~~

Unless ``breakpoints`` is passed explicitly, candidates for ``constrained``
and ``full-width`` are drawn from the ``resolutions`` option, which defaults
to a descending ladder of common screen widths ported from `unpic`_:

.. code-block:: text

    6016, 5120, 4480, 3840, 3200, 2560, 2048, 1920, 1668, 1280, 1080, 960, 828, 750, 640

``constrained`` keeps only the entries below twice the requested ``width``;
``full-width`` keeps the whole ladder.

.. _image_img_or_picture:

``<img>`` or ``<picture>``
~~~~~~~~~~~~~~~~~~~~~~~~~~

The element is the caller's choice, never the provider's, so switching
``UX_IMAGE_DSN`` never changes the markup:

* ``ux_image()`` and ``<twig:ux:image>`` always render a single ``<img>``. When
  the provider picks the output format from the request itself, like
  Cloudflare's ``format=auto``, that ``<img>`` still gets a modern format.
  When it cannot, like KeyCDN, the ``<img>`` uses the last entry of the
  ``formats`` option.
* ``ux_picture()`` and ``<twig:ux:picture>`` always render a ``<picture>``,
  with one ``<source type="image/…" srcset="…">`` per entry of the ``formats``
  option intersected with the provider's supported formats, in that order,
  followed by the ``<img>`` as the last-resort fallback. Every source names its
  format in its URLs, which suits a provider without automatic negotiation,
  and caches that ignore ``Vary: Accept``.

.. code-block:: html+twig

    <twig:ux:picture src="/uploads/hero.jpg" alt="Hero" width="800" height="450" />

    {{ ux_picture('/uploads/hero.jpg', 'Hero', {width: 800, height: 450}) }}

Both take the same props, options and attributes as ``<twig:ux:image>`` and
``ux_image()``.

A ``format`` prop short-circuits the choice: it names one format, so there is
nothing left to fall back to. ``ux_image()`` renders its ``<img>`` in that
format, and ``ux_picture()`` renders a ``<picture>`` that only holds that
``<img>``.

The ``<picture>`` emitted here only ever carries per-format fallbacks. It
never carries a different crop per media query (art direction); that is out
of scope for this version.

.. _image_providers:

Providers
---------

Parameter mapping
~~~~~~~~~~~~~~~~~

Every ``ImageTransformation`` property maps to a provider-specific query
parameter:

=========================== ================== ==============
``ImageTransformation``     `Cloudflare`_      `KeyCDN`_
=========================== ================== ==============
``width``                   ``width``          ``width``
``height``                  ``height``         ``height``
``format``                  ``format``         ``format``
``quality``                 ``quality``        ``quality``
``fit``: ``Fit::Cover``     ``fit=cover``      ``fit=cover``
=========================== ================== ==============

``Fit::Contain`` maps to ``fit=contain`` on every provider.

``operations`` (see
:ref:`Provider-specific operations <image_provider_operations>`) is merged
into the generated URL verbatim, once resolved for the active provider.

Supported formats and negotiation
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

============= ===================================== =========================
Provider      Supported formats                     Negotiates automatically?
============= ===================================== =========================
`Cloudflare`_ ``avif``, ``webp``, ``jpeg``, ``png`` **Yes**
`KeyCDN`_     ``webp``, ``jpeg``, ``png``           **No**
============= ===================================== =========================

Cloudflare negotiates natively, through its own ``format=auto``. KeyCDN has
no automatic format negotiation at all, and no AVIF support either: with it,
``ux_image()`` serves the last configured format KeyCDN supports, and
``ux_picture()`` is the way to offer WebP to the browsers that accept it (see
:ref:`<img> or <picture> <image_img_or_picture>`).

Cloudflare
~~~~~~~~~~

`Cloudflare Image Resizing`_ transforms images already served from your own
origin, through the ``/cdn-cgi/image/`` URL path on a Cloudflare zone. No
image-processing server of your own is needed.

.. code-block:: terminal

    $ composer require symfony/ux-cloudflare-image

.. code-block:: bash

    # .env.prod
    UX_IMAGE_DSN=cloudflare://cdn.example.com

The host is the domain proxied by your Cloudflare zone; it must be the
domain your origin images are served from. Image transformations must be
enabled on that zone before ``/cdn-cgi/image/`` URLs work — see
`Enable transformations`_ in the Cloudflare docs.

Extra operations, forwarded as-is: ``gravity``, ``dpr``, ``rotate``,
``trim``, ``blur``, ``brightness``, ``contrast``, ``gamma``, ``saturation``,
``sharpen``, ``background``, ``border``, ``anim``, ``metadata``,
``onerror``, ``compression``. See `Cloudflare's own options reference`_ for
what each one does.

KeyCDN
~~~~~~

`KeyCDN Image Processing`_ transforms images already served from your own
origin, through query string parameters appended to your zone's URL.

.. code-block:: terminal

    $ composer require symfony/ux-keycdn-image

.. code-block:: bash

    # .env.prod
    UX_IMAGE_DSN=keycdn://myzone.kxcdn.com

The host is your KeyCDN zone.

Extra operations, forwarded as-is: ``position``, ``enlarge``, ``trim``,
``crop``, ``bg``, ``rotate``, ``flip``, ``flop``, ``sharpen``, ``blur``,
``gamma``, ``grayscale``, ``progressive``, ``lossless``, ``metadata``. See
`KeyCDN's own parameter reference`_ for what each one does.

The package supports PHP 8.4 or later and Symfony 7.4 or 8.x.

.. _`the Symfony UX initiative`: https://ux.symfony.com/
.. _`unpic`: https://github.com/ascorbic/unpic-img
.. _`Cloudflare`: https://github.com/symfony/ux/blob/3.x/src/Image/src/Bridge/Cloudflare/README.md
.. _`Cloudflare Image Resizing`: https://developers.cloudflare.com/images/transform-images/
.. _`Cloudflare's own options reference`: https://developers.cloudflare.com/images/transform-images/transform-via-url/#options
.. _`Enable transformations`: https://developers.cloudflare.com/images/transform-images/#enable-transformations-via-dashboard
.. _`KeyCDN`: https://github.com/symfony/ux/blob/3.x/src/Image/src/Bridge/KeyCdn/README.md
.. _`KeyCDN Image Processing`: https://www.keycdn.com/support/image-processing
.. _`KeyCDN's own parameter reference`: https://www.keycdn.com/support/image-processing
