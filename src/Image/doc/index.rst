Symfony UX Image
================

**EXPERIMENTAL** This component is currently experimental and is likely
to change, or even change drastically.

Symfony UX Image renders responsive images in Symfony applications by
delegating every transformation to a URL-based image provider, such as
`Glide`_, `KeyCDN`_ or `Cloudflare`_. It is part of
`the Symfony UX initiative`_.

The package never decodes, resizes or encodes an image itself. Every
transformation is expressed as a URL, built by whichever provider is
active; the package's own job is generating that URL, a ``srcset``, a
``sizes`` attribute and a layout ``style``.

Installation
------------

Install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-image

Then install one of the provider bridges, for example `Glide`_:

.. code-block:: terminal

    $ composer require symfony/ux-glide-image

Rendering an image
------------------

The ``<twig:ux:image>`` component renders a single image:

.. code-block:: html+twig

    <twig:ux:image src="/uploads/hero.jpg" alt="Hero" width="800" height="450" />

The equivalent ``ux_image()`` Twig function is available for programmatic use,
for example when the source path is only known inside a Twig macro:

.. code-block:: twig

    {{ ux_image('/uploads/hero.jpg', 'Hero', {width: 800, height: 450}) }}

Both accept the same rendering options: the component exposes them as props,
the function takes them as its third, associative-array argument, keyed by
prop name in camelCase (``objectFit``, not ``object-fit``). An unknown key
there throws an ``InvalidArgumentException``.

HTML attributes are the component's alone. ``class``, ``data-*``, ``style``
and ``sizes`` reach the rendered ``<img>`` when they are passed to
``<twig:ux:image>``; ``ux_image()`` has no argument for them.

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
``fit``         ``cover|contain|scale-down|null`` ``null``
``format``      ``string|null``                   ``null``
``quality``     ``int|null`` (1 to 100)           ``null``
``priority``    ``bool``                          ``false``
``object-fit``  ``string``                        ``cover``
``breakpoints`` ``int[]|null``                    the built-in resolution ladder, see Layout and rendering below
``operations``  ``array<string, array>``          ``{}``
=============== ================================= ================================

``layout`` and its ``breakpoints``, ``sizes`` and generated ``style`` are
covered under Layout and rendering below. ``priority`` sets ``loading="eager"
fetchpriority="high"``; without it, an image gets ``loading="lazy"
fetchpriority="auto"``.

``format`` pins the output format for this one image, in place of both the
provider's own negotiation and the per-format ``<picture>`` fallbacks: a
single ``<img>`` is rendered in that format. A format the active provider
cannot produce throws an ``InvalidArgumentException`` naming its supported
list.

Any attribute not listed above (``class``, ``data-*``, a caller-supplied
``style`` or ``sizes``, …) is passed through to the rendered ``<img>``. A
caller-supplied ``style`` merges with the generated layout style instead of
replacing it; a caller-supplied ``sizes`` replaces the generated value.

Provider-specific operations
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Every provider accepts extra, provider-specific parameters beyond the common
``width``/``height``/``fit``/``format``/``quality`` set (see Providers below
for the full list per bridge). Pass them through ``operations``, keyed by
provider name:

.. code-block:: html+twig

    <twig:ux:image src="/uploads/hero.jpg" alt="Hero" width="800"
        :operations="{cloudflare: {gravity: 'auto'}, glide: {crop: 'smart'}}" />

At render time, only ``operations[activeProviderName]`` is read; the rest is
ignored. Keying by provider name is deliberate: the active DSN changes
between environments, and a flat, un-keyed ``gravity`` option would silently
vanish the moment the application switched from Cloudflare to another
provider. Passing an operation the active provider does not support throws
an ``InvalidArgumentException`` naming the provider's supported list.

Configuration
-------------

Configuration is done in your ``config/packages/ux_image.yaml`` file:

.. code-block:: yaml

    # config/packages/ux_image.yaml
    ux_image:
        provider: '%env(resolve:UX_IMAGE_DSN)%'
        formats: ['avif', 'webp', 'jpeg']

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
    UX_IMAGE_DSN=glide://default/images?source=%kernel.project_dir%/public/uploads&cache=%kernel.project_dir%/var/glide-cache

.. code-block:: bash

    # .env.prod
    UX_IMAGE_DSN=cloudflare://cdn.example.com

Each bridge is only registered when its Composer package is actually
installed. Install the one matching the scheme used in the DSN:

============== ================================================ ===========================================
Scheme         Install                                          DSN example
============== ================================================ ===========================================
``glide``      ``composer require symfony/ux-glide-image``      ``glide://default/images?source=…&cache=…``
``keycdn``     ``composer require symfony/ux-keycdn-image``     ``keycdn://myzone.kxcdn.com``
``cloudflare`` ``composer require symfony/ux-cloudflare-image`` ``cloudflare://cdn.example.com``
============== ================================================ ===========================================

See Providers below for what each DSN option means and how transformation
parameters map to that provider's own query string.

The ``formats`` option
~~~~~~~~~~~~~~~~~~~~~~

``formats`` is the candidate output format list, in preference order. What it
governs depends on the active provider:

* for a provider that does not negotiate the format itself, it is intersected
  with that provider's own supported formats (see Providers below) and each
  surviving entry becomes one ``<source>`` of the rendered ``<picture>`` (see
  Layout and rendering below). An empty intersection throws an exception
  naming both lists, so asking a provider that cannot encode AVIF to serve
  only AVIF fails at first render rather than serving the wrong format
  silently;
* for `Glide`_, it narrows the candidates the bridge's controller will
  negotiate ``fm=auto`` down to, with ``jpg`` as the last-resort fallback;
* for `Cloudflare`_, it has no effect: the choice is made inside Cloudflare,
  by its own ``format=auto``.

The default is ``['avif', 'webp', 'jpeg']``. For the non-negotiating and
`Glide`_ cases above, narrowing it is how an application whose image pipeline
cannot encode AVIF keeps AVIF off the wire; on `Cloudflare`_, ``formats`` is
ignored, so this does not apply there.

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

``object-fit`` overrides the ``object-fit`` value above; it defaults to
``cover``.

The resolution ladder
~~~~~~~~~~~~~~~~~~~~~

Unless ``breakpoints`` is passed explicitly, candidates for ``constrained``
and ``full-width`` are drawn from a built-in, descending resolution ladder,
ported from `unpic`_:

.. code-block:: text

    6016, 5120, 4480, 3840, 3200, 2560, 2048, 1920, 1668, 1280, 1080, 960, 828, 750, 640

``constrained`` keeps only the entries below twice the requested ``width``;
``full-width`` keeps the whole ladder.

``<img>`` or ``<picture>``
~~~~~~~~~~~~~~~~~~~~~~~~~~

The choice is the active provider's, unless the caller pins a ``format``: it
follows ``supportsAutoFormat()`` (see Providers below).

* When the provider can pick the output format from the request itself (for
  example through a native ``format=auto``, or a controller that negotiates
  it), a single ``<img>`` is rendered with one ``srcset`` across the
  breakpoints.
* When it cannot, a ``<picture>`` is rendered with one ``<source
  type="image/…" srcset="…">`` per entry of the ``formats`` option
  intersected with the provider's supported formats, in that order, followed
  by the ``<img>`` as the last-resort fallback.

KeyCDN has no automatic format negotiation and cannot encode to AVIF, so
``ux_image()`` always renders a ``<picture>`` for it, never a single
``<img>``.

This means the exact same template renders different markup depending on
which provider is active: switching ``UX_IMAGE_DSN`` between a
``<picture>``-based provider and an ``<img>``-based one changes the output
without any template change. This is deliberate — an application configured
for KeyCDN that emitted a single ``<img>`` would serve one hard-coded format
to every browser, negotiation or not.

A ``format`` prop short-circuits all of this: it names one format, so there is
nothing left to fall back to and a single ``<img>`` is rendered whatever the
provider supports.

The ``<picture>`` emitted here only ever carries per-format fallbacks. It
never carries a different crop per media query (art direction); that is out
of scope for this version.

Providers
---------

Parameter mapping
~~~~~~~~~~~~~~~~~

Every ``ImageTransformation`` property maps to a provider-specific query
parameter:

=========================== ============ ================== ==============
``ImageTransformation``     `Glide`_     `Cloudflare`_      `KeyCDN`_
=========================== ============ ================== ==============
``width``                   ``w``        ``width``          ``width``
``height``                  ``h``        ``height``         ``height``
``format``                  ``fm``       ``format``         ``format``
``quality``                 ``q``        ``quality``        ``quality``
``fit``: ``Fit::Cover``     ``fit=crop`` ``fit=cover``      ``fit=cover``
``fit``: ``Fit::ScaleDown`` ``fit=max``  ``fit=scale-down`` ``fit=inside``
=========================== ============ ================== ==============

``Fit::Contain`` maps to ``fit=contain`` on every provider.

``operations`` (see Provider-specific operations above) is merged into the
generated URL verbatim, once resolved for the active provider.

Supported formats and negotiation
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

============= ================================================================== =========================
Provider      Supported formats                                                  Negotiates automatically?
============= ================================================================== =========================
`Cloudflare`_ ``avif``, ``webp``, ``jpeg``, ``png``                              **Yes**
`Glide`_      ``avif``, ``webp``, ``jpeg``, ``pjpg``, ``png``, ``gif``, ``heic`` **Yes**
`KeyCDN`_     ``webp``, ``jpeg``, ``png``                                        **No**
============= ================================================================== =========================

Cloudflare negotiates natively, through its own ``format=auto``. Glide has no
such native value — negotiation is done by the controller this bridge ships,
which resolves ``fm=auto`` from the request's ``Accept`` header itself (see
below). KeyCDN has no automatic format negotiation at all, and no AVIF
support either, which is why ``ux_image()`` always renders a ``<picture>``
with one ``<source>`` per configured format for it, never a single ``<img>``
(see ``<img>`` or ``<picture>`` above).

Glide
~~~~~

`Glide`_ is the local, no-CDN provider: images are resized and encoded
on-the-fly by your own application through a controller this bridge ships,
from a source directory you control, with results cached to disk.

.. code-block:: terminal

    $ composer require symfony/ux-glide-image

.. code-block:: bash

    # .env
    UX_IMAGE_DSN=glide://default/images?source=%kernel.project_dir%/public/uploads&cache=%kernel.project_dir%/var/glide-cache&sign_key=s3cret

The host (``default`` above) is always a placeholder — Glide has no remote
endpoint, only a local source and cache — so what matters is the DSN's path
and its query options:

================== =======================================================================
DSN part           Meaning
================== =======================================================================
path (``/images``) the URL prefix images are served under, e.g. ``/images/hero.jpg?w=800``
``source``         absolute path to the directory holding your original images
``cache``          absolute path to the directory Glide writes resized/encoded images to
``sign_key``       optional; when set, every request must carry a valid ``s=`` signature
``max_image_size`` optional; output pixel cap per image, ``25000000`` by default
================== =======================================================================

The bridge ships a controller but not a route with a fixed prefix. **The
prefix your application imports it under must match the DSN's path
exactly** — the bundle has no way to enforce this at compile time, and a
drift between the two means ``ux_image()`` generates URLs your own route
cannot match:

.. code-block:: yaml

    # config/routes/ux_image_glide.yaml
    ux_image_glide:
        resource: '@UXImageBundle/config/routes/glide.php'
        prefix: /images

When ``sign_key`` is set, the controller validates the ``s=`` signature
**server-side** before doing anything else — it is not merely generated and
left unchecked. An unsigned request against a signed setup gets a plain 403,
with no signature or key echoed back.

Setting a ``sign_key`` is **strongly recommended in production**. Without one
the route resizes and caches whatever anyone asks it for, and every distinct
parameter combination costs one encode and one new cache file.
``max_image_size`` caps how large a single output can get — Glide scales an
oversized request down to it rather than refusing — but only a signature stops
the request from being served at all.

Extra operations, forwarded as-is: ``crop``, ``or``, ``bri``, ``con``,
``gam``, ``sharp``, ``blur``, ``pixel``, ``filt``, ``bg``, ``border``. See
`Glide's own API reference`_ for what each one does.

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
.. _`Glide`: https://github.com/symfony/ux/blob/3.x/src/Image/src/Bridge/Glide/README.md
.. _`Glide's own API reference`: https://glide.thephpleague.com/4.0/api/quick-reference/
.. _`Cloudflare`: https://github.com/symfony/ux/blob/3.x/src/Image/src/Bridge/Cloudflare/README.md
.. _`Cloudflare Image Resizing`: https://developers.cloudflare.com/images/transform-images/
.. _`Cloudflare's own options reference`: https://developers.cloudflare.com/images/transform-images/transform-via-url/#options
.. _`Enable transformations`: https://developers.cloudflare.com/images/transform-images/#enable-transformations-via-dashboard
.. _`KeyCDN`: https://github.com/symfony/ux/blob/3.x/src/Image/src/Bridge/KeyCdn/README.md
.. _`KeyCDN Image Processing`: https://www.keycdn.com/support/image-processing
.. _`KeyCDN's own parameter reference`: https://www.keycdn.com/support/image-processing
