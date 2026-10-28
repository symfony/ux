Symfony UX Toolkit
==================

**EXPERIMENTAL** This component is currently experimental and is likely
to change, or even change drastically.

Symfony UX Toolkit gives you ready-to-use UI recipes for your Symfony
application. It is part of `the Symfony UX initiative`_.

It is not a library that you depend on at runtime. The ``ux:install`` command
copies the source of a recipe into your project, and from there the code is
yours: you read it, edit it and commit it like any other template of your
application.

Nothing is hidden in ``vendor/``, and nothing updates it for you either. That
trade is what makes the Toolkit different from a component library, and it is
described in `The Recipe Is Yours, For Worse and For Better`_.

Installation
------------

.. code-block:: terminal

    $ composer require --dev symfony/ux-toolkit

The package only registers console commands, so it is needed while you install
recipes, not to render them.

Kits and Recipes
----------------

A *recipe* is a pack of files and dependencies: Twig components, Stimulus
controllers, and the Composer or npm packages they need to run. ``button`` and
``dialog`` are recipes.

A *kit* is a collection of recipes sharing one design system, like Shadcn UI or
Bootstrap. The kits shipped with the Toolkit are browsable in the
`ux-toolkit repository <https://github.com/symfony/ux-toolkit/tree/3.x/kits>`_.

Installing a Recipe
-------------------

Run the ``ux:install`` command with the name of the recipe you want:

.. code-block:: terminal

    $ php bin/console ux:install dialog

The command asks which kit to take the recipe from, copies its files into
your application and lists the dependencies the recipe needs.

The files land where they are meant to be used: Twig templates under
``templates/``, Stimulus controllers under ``assets/``, and if a file already
exists, it is not replaced without asking you first, so a recipe you have
already customized cannot be overwritten by accident. When Git is installed, a
diff between your file and the recipe file is shown before the question, so you
can see exactly what would change. When the file is identical to the recipe, it
is left as is and no question is asked.

A recipe can depend on other recipes and on packages, and the two are handled
differently:

* Other recipes it depends on are copied in the same run: installing ``dialog``
  from the Shadcn kit also writes ``button``;
* Composer and npm packages are not installed for you: the command prints the
  ``composer require``, ``npm install`` or ``php bin/console importmap:require``
  command to run yourself.

.. tip::

    Run ``php bin/console ux:install`` without any argument and the command
    guides you: it asks for the kit, then for the recipe. Recipe names are
    matched case-insensitively, and when a name does not exist, the command
    suggests the closest ones it knows.

Choosing a Kit
--------------

Four kits are shipped with the Toolkit:

* ``shadcn``, `Shadcn UI <https://ux.symfony.com/toolkit/kits/shadcn>`_
* ``flowbite-4``, `Flowbite v4 <https://ux.symfony.com/toolkit/kits/flowbite-4>`_
* ``bootstrap``, `Bootstrap 5.3 <https://ux.symfony.com/toolkit/kits/bootstrap>`_
* ``common``, `design-system agnostic recipes <https://ux.symfony.com/toolkit/kits/common>`_
  for common Symfony needs, such as logout links

Pass ``--kit`` to skip the question:

.. code-block:: terminal

    $ php bin/console ux:install button --kit=shadcn

Browse the recipes of each kit, with a live preview, on
`ux.symfony.com <https://ux.symfony.com/toolkit>`_.

Choosing Where Recipes Are Installed
------------------------------------

.. versionadded:: 3.7

    The ``components`` and ``controllers`` options were added in UX Toolkit 3.7.

By default, ``ux:install`` writes Twig components to ``templates/components/``
and Stimulus controllers to ``assets/controllers/``. The ``components`` and
``controllers`` options under ``ux_toolkit`` change that. Each one takes a
``dir`` and an optional ``subdirectory``. Paths are relative to the project
directory.

The ``dir`` Option
~~~~~~~~~~~~~~~~~~

``dir`` does not choose a new location. It tells the Toolkit where your
application already loads files from, and it must be the same value as in that
configuration. Otherwise, the installed components and controllers are not
found. The Toolkit cannot read these settings itself.

* ``components.dir`` must match the directory TwigComponent loads anonymous
  components from: the Twig ``default_path`` followed by TwigComponent's
  ``anonymous_template_directory`` (default ``templates/components``).
* ``controllers.dir`` must match the directory your Stimulus setup loads
  controllers from. With AssetMapper, it is an entry of
  ``stimulus.controller_paths``. With Reprise, it is the ``controllersDir``
  option. With Webpack Encore, it is the directory given to
  ``require.context()`` in ``assets/bootstrap.js`` (default
  ``assets/controllers``).

For example, an AssetMapper app that loads controllers from
``assets/js/controllers`` must use the same path in both files:

.. code-block:: yaml

    # config/packages/stimulus.yaml
    stimulus:
        controller_paths:
            - '%kernel.project_dir%/assets/js/controllers'

    # config/packages/ux_toolkit.yaml
    ux_toolkit:
        controllers:
            dir: 'assets/js/controllers'

The ``subdirectory`` Option
~~~~~~~~~~~~~~~~~~~~~~~~~~~

``subdirectory`` is the option you can choose freely. The kit files go into
that subdirectory of ``dir``, to keep them apart from your own components:

.. code-block:: yaml

    # config/packages/ux_toolkit.yaml
    ux_toolkit:
        components:
            subdirectory: 'ui'
        controllers:
            subdirectory: 'ui'

TwigComponent and Stimulus derive names from the file path, so the names of the
kit files get the subdirectory as a prefix. ``ux:install`` rewrites the copied
files so the recipe keeps working. With ``subdirectory: ui`` on both options,
``<twig:Button>`` becomes ``<twig:ui:Button>``, and the ``dialog`` controller
becomes ``ui--dialog``. Its ``data-controller``, ``data-action``,
``data-*-target``, ``data-*-value``, ``data-*-class`` and ``data-*-param``
attributes are rewritten in templates and controllers. In your own templates,
you then write ``<twig:ui:Dialog>``.

Validation
~~~~~~~~~~

``dir`` must be a relative path inside the project. Absolute paths and paths
going up with ``..`` are rejected. ``subdirectory`` can only contain letters,
digits, ``_`` and ``-``, with ``/`` between directories. Names that do not
belong to the kit (like ``<twig:ux:icon>`` or the ``live`` controller) are
never rewritten.

For Kit Authors
~~~~~~~~~~~~~~~

.. versionadded:: 3.7

    The ``%COMPONENTS_DIR%`` and ``%CONTROLLERS_DIR%`` placeholders were added in
    UX Toolkit 3.7.

In a recipe ``manifest.json``, the destinations of ``copy-files`` can use the
``%COMPONENTS_DIR%`` and ``%CONTROLLERS_DIR%`` placeholders:

.. code-block:: json

    {
        "copy-files": {
            "templates/components/": "%COMPONENTS_DIR%/",
            "assets/controllers/": "%CONTROLLERS_DIR%/"
        }
    }

They resolve to ``dir`` followed by ``subdirectory``. All kits shipped with the
Toolkit use them. A plain path still works, but its files are copied as they
are and never rewritten. An unknown placeholder is an error.

The Recipe Is Yours, For Worse and For Better
---------------------------------------------

Installing a recipe copies its files into your application, and the Toolkit
then steps out of the way. The code is part of your project, like any template
you wrote yourself.

The consequence is that there is no upgrade path. Fixes, new options and new
features added to a kit never reach a recipe you have already installed, and
installing the same recipe today and in six months gives two different
versions of it.

Your version control system is what closes that gap. Commit right after
installing a recipe, and when you want the newer version, run ``ux:install``
again and read the diff: it shows exactly what changed in the kit and what you
have changed yourself, and you keep what you want from each side. The command
asks before overwriting, so nothing is lost if you would rather not.

In exchange, the recipe really is yours. Adapting one is editing a template:
no override mechanism to learn, no configuration to bend, and no limit to the
options its author thought of. That is the idea the Toolkit takes from
`Shadcn UI <https://ui.shadcn.com/>`_, and the reason the files are copied
rather than shipped in ``vendor/``.

.. _`the Symfony UX initiative`: https://ux.symfony.com/
