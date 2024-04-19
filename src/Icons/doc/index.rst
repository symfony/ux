Symfony UX Icons
================

The ``symfony/ux-icons`` package offers simple and intuitive ways to render
SVG icons in your Symfony application. It provides a Twig function to include
any local or remote icons from your templates.

UX Icons gives you a direct access to over 200,000 vector icons from popular
icon sets such as FontAwesome, Bootstrap Icons, Tabler Icons, Google Material
Design Icons, etc.

Installation
------------

.. code-block:: terminal

    $ composer require symfony/ux-icons

SVG Icons
---------

`SVG`_ (Scalable Vector Graphics) is an XML-based vector image format, allowing
resolution-independent graphics while maintaining a small file size. SVG icons
can be embedded in the HTML code, styled with CSS, and animated with JavaScript.

UX Icons allows you to use icons from the most popular icon sets, but also gives you
the flexibility to compose your own collection, mixing icons from different sets with
your own.

Icon Names
~~~~~~~~~~

Icons are referenced using an unique identifier that follows one of the following syntaxes:

* ``prefix:name``  (e.g. ``mdi:check``, ``bi:check``, ``editor:align-left``)
* ``name`` only (e.g. ``check``, ``close``, ``menu``)

The icon ``name`` is the same as the file name without the file extension (e.g. ``check.svg`` -> ``check``).

.. caution::

    The name must match a standard ``slug`` format: ``[a-z0-9-]+(-[0-9a-z])+``.

Depending on your configuration, the ``prefix`` can be the name of an icon set, a directory
where the icon is located, or a combination of both.

For example, the ``bi`` prefix refers to the Bootstrap Icons set, while the ``header`` prefix
refers to the icons located in the ``header`` directory.

Loading Icons
-------------

.. code-block:: twig

    {# includes the contents of the 'assets/icons/user-profile.svg' file in the template #}
    {{ ux_icon('user-profile') }}

    {# icons stored in subdirectories must use the 'subdirectory_name:file_name' syntax
       (e.g. this includes 'assets/icons/admin/user-profile.svg') #}
    {{ ux_icon('admin:user-profile') }}

    {# this downloads the 'user-solid.svg' icon from the 'Flowbite' icon set via ux.symfony.com
       and embeds the downloaded SVG contents in the template #}
    {{ ux_icon('flowbite:user-solid') }}

The ``ux_icon()`` function defines a second optional argument where you can
define the HTML attributes added to the ``<svg>`` element:

.. code-block:: html+twig

    {{ ux_icon('user-profile', {class: 'w-4 h-4'}) }}
    {# renders <svg class="w-4 h-4"> ... </svg> #}

    {{ ux_icon('user-profile', {height: '16px', width: '16px', aria-hidden: true}) }}
    {# renders <svg height="16" width="16" aria-hidden="true"> ... </svg> #}

Icon Sets
~~~~~~~~~

There are many icon sets available, each with their own unique style and set of
icons, providing a wide range of icons for different purposes, while maintaining
a consistent look and feel across your application. Here are some of the most
popular icon sets available:

========================  =======  ==========  ===============  =====================
Icon Set                    Icons  License     Prefix           Example
========================  =======  ==========  ===============  =====================
`Bootstrap Icons`_           2000  MIT         ``bi``           ``bi:check``
`Boxicons`_                  1700  MIT         ``bx``           ``bx:check``
`Flowbite`_                  1000  MIT         ``flowbite``     ``flowbite:check``
`FontAwesome Icons`_         7000  CC BY 4.0   ``fa6-regular``  ``fa6-regular:check``
`Heroicons`_                  300  MIT         ``heroicons``    ``heroicons:check``
`Iconoir`_                   1500  MIT         ``iconoir``      ``iconoir:check``
`Ionicons`_                  1300  MIT         ``ion``          ``ion:check``
`Lucide Icons`_              1500  MIT         ``lucide``       ``lucide:check``
`Material Design Icons`_     5000  Apache 2    ``mdi``          ``mdi:check``
`Octicons`_                   600  MIT         ``octicon``      ``octicon:check``
`Phosphor Icons`_            7000  MIT         ``ph``           ``ph:check``
`Tabler Icons`_              5200  MIT         ``tabler``       ``tabler:check``
========================  =======  ==========  ===============  =====================

To see the full list of available icon sets, visit `ux.symfony.com/icons`_.

Search Icon sets
~~~~~~~~~~~~~~~~

You can use the ``ux:icons:search`` command to search for icon sets, or to find
the prefix of a specific icon set:

.. code-block:: terminal

    $ php bin/console ux:icons:search tabler

     -------------- ------- --------- -------- --------------
      Icon set       Icons   License   Prefix   Example
     -------------- ------- --------- -------- --------------
      Tabler Icons    5219   MIT       tabler   tabler:alien
     -------------- ------- --------- -------- --------------

    Search "arrow" in Tabler Icons icons:

     php bin/console ux:icons:search tabler arrow

Search Icons
~~~~~~~~~~~~

You can also search for icons within a specific icon set. To search for "arrow"
icons in the "Tabler Icons" set, use the following command:

.. code-block:: terminal

    $ php bin/console ux:icons:search tabler arrow

    Searching Tabler Icons icons "arrow"...
    Found 64 icons.
     ------------------------------------------ ------------------------------------------
      tabler:archery-arrow                       tabler:arrow-autofit-up
      tabler:arrow-back                          tabler:arrow-back-up
      tabler:arrow-badge-down                    tabler:arrow-badge-up
      tabler:arrow-badge-up-filled               tabler:arrow-bar-both
      tabler:arrow-bar-down                      tabler:arrow-bar-left
      tabler:arrow-bar-right                     tabler:arrow-bar-to-up
      tabler:arrow-bar-up                        tabler:arrow-bear-left
      tabler:arrow-big-down                      tabler:arrow-big-down-filled
      tabler:arrow-big-down-line                 tabler:arrow-big-left
      tabler:arrow-big-left-filled               tabler:arrow-big-left-line
      tabler:arrow-big-right                     tabler:arrow-big-right-filled
      tabler:arrow-big-right-line                tabler:arrow-big-up
     ------------------------------------------ ------------------------------------------

     Page 1/3. Continue? (yes/no) [yes]:
     >

HTML Syntax
~~~~~~~~~~~

In addition to the ``ux_icon()`` function explained in the previous sections,
this package also supports an alternative HTML syntax based on the ``<twig:UX:Icon>``
tag:

.. code-block:: html

    <!-- renders "user-profile.svg" -->
    <twig:UX:Icon name="user-profile" class="w-4 h-4" />
    <!-- renders "admin/user-profile.svg" -->
    <twig:UX:Icon name="admin:user-profile" class="w-4 h-4" />
    <!-- renders 'user-solid.svg' icon from 'Flowbite' icon set via ux.symfony.com -->
    <twig:UX:Icon name="flowbite:user-solid" />

    <!-- you can also add any HTML attributes -->
    <twig:UX:Icon name="user-profile" height="16" width="16" aria-hidden="true" />

.. note::

    To use the HTML syntax, the ``symfony/ux-twig-component`` package must be
    installed in your project.

Downloading Icons
-----------------

This package doesn't include any icons, but provides access to over 200,000
open source icons.

Local SVG Icons
~~~~~~~~~~~~~~~

If you already have the SVG icon files to use in your project, store them in the
``assets/icons/`` directory and commit them. The name of the file is used as the
*name* of the icon (``icon_name.svg`` will be named ``icon_name``). If located in
a subdirectory, the *name* will be ``subdirectory:icon_name``.

.. code-block:: text

    your-project/
    ├─ assets/
    │  └─ icons/
    │     ├─ bi/
    │     │  └─ pause-circle.svg
    │     │  └─ play-circle.svg
    │     ├─ header/
    │     │  ├─ logo.svg
    │     │  └─ menu.svg
    │     ├─ close.svg
    │     ├─ menu.svg
    │     └─ ...
    └─ ...

Icons On-Demand
~~~~~~~~~~~~~~~

`ux.symfony.com/icons`_ has a huge searchable repository of icons from many
different sets. This package provides a way to include any icon found on this
site *on-demand*:

1. Visit `ux.symfony.com/icons`_ and search for an icon you'd like to use. Once
   you find one you like, copy one of the code snippets provided.
2. Paste the snippet into your Twig template and the icon will be automatically
   fetched (and cached).

That's all. This works by using the `Iconify`_ API (to which `ux.symfony.com/icons`_
is a frontend for) to fetch the icon and render it in place. This icon is then cached
for future requests for the same icon.

.. note::

    `Local SVG Icons`_ of the same name will have precedence over *on-demand* icons.

Importing Icons
---------------

While *on-demand* icons are great during development, they require HTTP requests
to fetch the icon and always use the *latest version* of the icon. It's possible
that the icon could change or be removed in the future. Additionally, the cache
warming process will take significantly longer if using many *on-demand* icons.

That's why this package provides a command to download the open source icons into
the ``assets/icons/`` directory. You can think of importing an icon as *locking it*
(similar to how ``composer.lock`` *locks* your dependencies):

.. code-block:: terminal

    # icon will be saved in `assets/icons/flowbite/user-solid.svg` and you can
    # use it with the name: `flowbite:user-solid`
    $ php bin/console ux:icons:import flowbite:user-solid

    # it's also possible to import several icons at once
    $ php bin/console ux:icons:import flowbite:user-solid flowbite:home-solid

.. note::

    Imported icons must be committed to your repository.

Locking On-Demand Icons
~~~~~~~~~~~~~~~~~~~~~~~

You can *lock* (import) all the `*on-demand* <Icons On-Demand>`_ icons you're using in your project by
running the following command:

.. code-block:: terminal

    $ php bin/console ux:icons:lock

This command only imports icons that do not already exist locally. You can force
the report to overwrite existing icons by using the ``--force`` option:

.. code-block:: terminal

    $ php bin/console ux:icons:lock --force

Rendering Icons
---------------

.. code-block:: twig

    {# includes the contents of the 'assets/icons/user-profile.svg' file in the template #}
    {{ ux_icon('user-profile') }}

    {# icons stored in subdirectories must use the 'subdirectory_name:file_name' syntax
       (e.g. this includes 'assets/icons/admin/user-profile.svg') #}
    {{ ux_icon('admin:user-profile') }}

    {# this downloads the 'user-solid.svg' icon from the 'Flowbite' icon set via ux.symfony.com
       and embeds the downloaded SVG contents in the template #}
    {{ ux_icon('flowbite:user-solid') }}

HTML Syntax
~~~~~~~~~~~

.. code-block:: html+twig

    <twig:UX:Icon name="user-profile" />

    {# Renders "user-profile.svg" #}
    <twig:UX:Icon name="user-profile" class="w-4 h-4" />

    {# Renders "sub-dir/user-profile.svg" (sub-directory) #}
    <twig:UX:Icon name="sub-dir:user-profile" class="w-4 h-4" />

    {# Renders "flowbite:user-solid" from ux.symfony.com #}
    <twig:UX:Icon name="flowbite:user-solid" />

.. note::

    ``symfony/ux-twig-component`` is required to use the HTML syntax.

Default Attributes
~~~~~~~~~~~~~~~~~~

You can set default attributes for all icons in your configuration. These attributes will be
added to all icons unless overridden by the second argument of the ``ux_icon`` function.

.. code-block:: yaml

    # config/packages/ux_icons.yaml
    ux_icons:
        default_icon_attributes:
            fill: currentColor

Now, all icons will have the ``fill`` attribute set to ``currentColor`` by default.

.. code-block:: twig

    # renders "user-profile.svg" with fill="currentColor"
    {{ ux_icon('user-profile') }}

    # renders "user-profile.svg" with fill="red"
    {{ ux_icon('user-profile', {fill: 'red'}) }}

Accessibility
-------------

Icons add visual elements to your website and they can be a challenge for accessibility.
According to the `W3C guide about SVG icon accessibility`_, there are
three methods to improve icons accessibility, depending on the context.

**Informative icons**
    They convey information or a function. They should define a text alternative
    that presents the same content or function via the ``aria-label`` attribute
    used by screen readers and other assistive technologies:

    .. code-block:: twig

        Today's weather:
        {{ ux_icon('cloud-rain', {'aria-label': 'Rainy weather'}) }}

**Functional icons**
    They are interactive and perform a function. They should define a text alternative
    that presents the same content or function via the ``aria-label`` attribute
    used by screen readers and other assistive technologies:

    .. code-block:: twig

        {{ ux_icon('user-profile', {class: 'w-4 h-4', 'aria-label': 'User Profile'}) }}

**Decorative icons**
    They are purely decorative and do not convey any meaning or function. They
    should be hidden from screen readers using the ``aria-hidden`` attribute.

    .. code-block:: html

        <a href="/profile">
            <svg viewBox="0 0 24 24" class="w-4 h-4" aria-hidden="true">
                <!-- ... -->
            </svg>
            Back to profile
        </a>

That is why the ``ux_icon()`` function and the ``<twig:ux:icon>`` component add
``aria-hidden="true"`` attribute **automatically** to icons not having at least one
of the following attributes: ``aria-label``, ``aria-labelledby`` or ``title``.

.. note::

    If you don't want to set ``aria-hidden="true"`` for a specific icon, you can
    explicitly set the ``aria-hidden`` attribute to ``false``:

    .. code-block:: twig+html

        <twig:UX:Icon name="user-profile" aria-hidden="false" />


Performance
-----------

The UX Icons component is designed to be fast. The following are some of
the optimizations made to ensure the best performance possible.

Caching
-------

On-Demand VS Import
^^^^^^^^^^^^^^^^^^^

While *on-demand* icons are great during development, they require HTTP requests to fetch the icon
and always use the *latest version* of the icon. It's possible the icon could change or be removed
in the future. Additionally, the cache warming process will take significantly longer if using
many _on-demand_ icons. You can think of importing the icon as *locking it* (similar to how
``composer.lock`` _locks_ your dependencies).

Icon Caching
~~~~~~~~~~~~

To avoid having to parse icon files on every request, icons are cached.
In production, you can pre-warm the cache by running the following command:

.. code-block:: terminal

    $ php bin/console ux:icons:warm-cache

This command looks in all your Twig templates for ``ux_icon()`` calls and
``<twig:UX:Icon>`` tags and caches the icons it finds.

.. caution::

    Icons that have a name built dynamically will not be cached. It's advised to
    have the icon name as a string literal in your templates.

    .. code-block:: twig

        {# This will be cached #}
        {{ ux_icon('flag-fr') }}

        {# This will NOT be cached #}
        {{ ux_icon('flag-' ~ locale) }}

        {# in this example, both "flag-fr" and "flag-de" will be cached #}
        {% set flags = {fr: 'flag-fr', de: 'flag-de'} %}
        {{ ux_icon(flags[locale]) }}

.. note::

    During development, if you modify an icon, you will need to clear the cache
    (``bin/console cache:clear``) to see the changes.

.. tip::

    If using `symfony/asset-mapper`_, the cache is warmed automatically when running ``asset-map:compile``.

TwigComponent
~~~~~~~~~~~~~

The ``ux_icon()`` function is optimized to be as fast as possible. To deliver the
same level of performance when using the HTML syntax (``<twig:UX:Icon name="..." />``),
the TwigComponent overhead is reduced by calling the IconRenderer immediately and
returning the HTML output.

.. warning::

    The <twig:UX:Icon> component does not support embedded content.

    .. code-block:: twig+html

        {# The 🧸 will be ignore in the HTML output #}
        <twig:UX:Icon name="user-profile" class="w-4 h-4">🧸</twig:UX:Icon>

        {# Renders "user-profile.svg" #}
        <svg viewBox="0 0 24 24" class="w-4 h-4">
            <path fill="currentColor" d="M21 7L9 19l-5.5-5.5l1.41-1.41L9 16.17L19.59 5.59z"/>
        </svg>

Configuration
-------------

The UX Icons integrates seamlessly in Symfony applications. All these options are configured under
the ``ux_icons`` key in your application configuration.

.. code-block:: yaml

    # config/packages/ux_icons.yaml
    ux_icons:
        {# ... #}

Debugging Configuration
~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: terminal

    # Displays the default config values
    $ php bin/console config:dump-reference ux_icons

    # Displays the actual config values used by your application
    $ php bin/console debug:config ux_icons

Full Configuration
~~~~~~~~~~~~~~~~~~

.. code-block:: yaml

    # config/packages/ux_icons.yaml
    ux_icons:
        # The local directory where icons are stored.
        icon_dir: '%kernel.project_dir%/assets/icons'

        # Default attributes to add to all icons.
        default_icon_attributes:
            # Default:
            fill: currentColor

        # Configuration for the "on demand" icons powered by Iconify.design.
        iconify:
           enabled: true

           # Whether to use the "on demand" icons powered by Iconify.design.
           on_demand: true

           # The endpoint for the Iconify API.
           endpoint: 'https://api.iconify.design'

Learn more
----------

* :doc:`Creating and Using Templates </templates>`
* :doc:`How to manage CSS and JavaScript assets in Symfony applications </frontend>`

.. _`SVG`: https://en.wikipedia.org/wiki/SVG
.. _`ux.symfony.com/icons`: https://ux.symfony.com/icons
.. _`Iconify`: https://iconify.design
.. _`symfony/asset-mapper`: https://symfony.com/doc/current/frontend/asset_mapper.html
.. _`W3C guide about SVG icon accessibility`: https://design-system.w3.org/styles/svg-icons.html#svg-accessibility
.. _`Bootstrap Icons`: https://icons.getbootstrap.com/
.. _`Boxicons`: https://boxicons.com/
.. _`Flowbite`: https://github.com/themesberg/flowbite
.. _`FontAwesome Icons`: https://github.com/FortAwesome/Font-Awesome
.. _`Heroicons`: https://github.com/tailwindlabs/heroicons
.. _`Iconoir`: https://github.com/iconoir-icons/iconoir
.. _`Ionicons`: https://github.com/ionic-team/ionicons
.. _`Lucide Icons`: https://github.com/lucide-icons/lucide
.. _`Material Design Icons`: https://github.com/google/material-design-icons
.. _`Octicons`: https://github.com/primer/octicons/
.. _`Phosphor Icons`: https://github.com/phosphor-icons/homepage
.. _`Tabler Icons`: https://github.com/tabler/tabler-icons
