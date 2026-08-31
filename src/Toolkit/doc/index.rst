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
already customized cannot be overwritten by accident.

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
* ``flowbite-4``, `Flowbite v4 <https://ux.symfony.com/toolkit/kits/flowbite>`_
* ``bootstrap``, `Bootstrap 5.3 <https://ux.symfony.com/toolkit/kits/bootstrap>`_
* ``common``, `design-system agnostic recipes <https://ux.symfony.com/toolkit/kits/common>`_
  for common Symfony needs, such as logout links

Pass ``--kit`` to skip the question:

.. code-block:: terminal

    $ php bin/console ux:install button --kit=shadcn

Browse the recipes of each kit, with a live preview, on
`ux.symfony.com <https://ux.symfony.com/toolkit>`_.

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
