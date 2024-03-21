Symfony UX Icons
================

This package provides utilities to work with SVG icons. It renders both local
and remote icons in your Twig templates. It provides direct access to over
200,000 open source vector icons from popular icon sets such as FontAwesome,
Bootstrap Icons, Tabler Icons, Google Material Design Icons, etc.

Installation
------------

.. code-block:: terminal

    $ composer require symfony/ux-icons

Usage
-----

This package provides a ``ux_icon()`` Twig function to define the icons that you
want to include in the templates:

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

On-Demand Open Source Icons
~~~~~~~~~~~~~~~~~~~~~~~~~~~

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

That's why this package provices a command to download the open source icons into
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

Caching
-------

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

Full Default Configuration
--------------------------

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

.. _`ux.symfony.com/icons`: https://ux.symfony.com/icons
.. _`Iconify`: https://iconify.design
.. _`symfony/asset-mapper`: https://symfony.com/doc/current/frontend/asset_mapper.html
