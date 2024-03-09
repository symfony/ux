Symfony UX Icons
================

Renders local and remote SVG icons in your Twig templates.

.. code-block:: html+twig

    {{ ux_icon('mdi:symfony', {class: 'w-4 h-4'}) }}
    {# or #}
    <twig:UX:Icon name="mdi:check" class="w-4 h-4" />

    {# renders as: #}
    <svg viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path fill="currentColor" d="M21 7L9 19l-5.5-5.5l1.41-1.41L9 16.17L19.59 5.59z"/></svg>

Installation
------------

.. code-block:: terminal

    $ composer require symfony/ux-icons

Icons?
------

No icons are provided by this package but there are several ways to include and render icons.

Local SVG Icons
~~~~~~~~~~~~~~~

Add your svg icons to the ``assets/icons/`` directory and commit them.
The name of the file is used as the _name_ of the icon (``name.svg`` will be named ``name``).
If located in a subdirectory, the _name_ will be ``sub-dir:name``.

Icons On-Demand
~~~~~~~~~~~~~~~

`ux.symfony.com/icons`_ has a huge searchable repository of icons
from many different sets. This package provides a way to include any icon found on this site _on-demand_.

1. Visit `ux.symfony.com/icons`_ and search for an icon
   you'd like to use. Once you find one you like, copy one of the code snippets provided.
2. Paste the snippet into your twig template and the icon will be automatically fetched (and cached).
3. That's it!

This works by using the `Iconify`_ API (to which `ux.symfony.com/icons`_
is a frontend for) to fetch the icon and render it in place. This icon is then cached for future requests
for the same icon.

.. note::

    `Local SVG Icons`_ of the same name will have precedence over _on-demand_ icons.

Import Command
^^^^^^^^^^^^^^

You can import any icon from `ux.symfony.com/icons`_ to your local
directory using the ``ux:icons:import`` command:

.. code-block:: terminal

    $ php bin/console ux:icons:import flowbite:user-solid # saved as `flowbite/user-solid.svg` and name is `flowbite:user-solid`

    # import several at a time
    $ php bin/console ux:icons:import flowbite:user-solid flowbite:home-solid

.. note::

    Imported icons must be committed to your repository.

On-Demand VS Import
^^^^^^^^^^^^^^^^^^^

While *on-demand* icons are great during development, they require http requests to fetch the icon
and always use the *latest version* of the icon. It's possible the icon could change or be removed
in the future. Additionally, the cache warming process will take significantly longer if using
many _on-demand_ icons. You can think of importing the icon as *locking it* (similar to how
``composer.lock`` _locks_ your dependencies).

Usage
-----

.. code-block:: html+twig

    {{ ux_icon('user-profile', {class: 'w-4 h-4'}) }} <!-- renders "user-profile.svg" -->

    {{ ux_icon('sub-dir:user-profile', {class: 'w-4 h-4'}) }} <!-- renders "sub-dir/user-profile.svg" (sub-directory) -->

    {{ ux_icon('flowbite:user-solid') }} <!-- renders "flowbite:user-solid" from ux.symfony.com -->

HTML Syntax
~~~~~~~~~~~

.. note::

    ``symfony/ux-twig-component`` is required to use the HTML syntax.

.. code-block:: html

    <twig:UX:Icon name="user-profile" class="w-4 h-4" /> <!-- renders "user-profile.svg" -->

    <twig:UX:Icon name="sub-dir:user-profile" class="w-4 h-4" /> <!-- renders "sub-dir/user-profile.svg" (sub-directory) -->

    <twig:UX:Icon name="flowbite:user-solid" /> <!-- renders "flowbite:user-solid" from ux.symfony.com -->

Caching
-------

To avoid having to parse icon files on every request, icons are cached.

In production, you can pre-warm the cache by running the following command:

.. code-block:: terminal

    $ php bin/console ux:icons:warm-cache

This command looks in all your twig templates for ``ux_icon`` calls and caches the icons it finds.

.. note::

    During development, if you modify an icon, you will need to clear the cache (``bin/console cache:clear``)
    to see the changes.

.. tip::

    If using `symfony/asset-mapper`_, the cache is warmed automatically when running ``asset-map:compile``.

Full Default Configuration
--------------------------

.. code-block:: yaml

    ux_icons:
        # The local directory where icons are stored.
        icon_dir: '%kernel.project_dir%/assets/icons'

        # Default attributes to add to all icons.
        default_icon_attributes:
            # Default:
            fill: currentColor

        # Configuration for the "on demand" icons powered by Iconify.design.
        iconify:
           enabled:              true

           # Whether to use the "on demand" icons powered by Iconify.design.
           on_demand:            true

           # The endpoint for the Iconify API.
           endpoint:             'https://api.iconify.design'

.. _`ux.symfony.com/icons`: https://ux.symfony.com/icons
.. _`Iconify`: https://iconify.design
.. _`symfony/asset-mapper`: https://symfony.com/doc/current/frontend/asset_mapper.html
