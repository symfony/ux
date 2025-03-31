Symfony UX Toolkit
==================

**EXPERIMENTAL** This component is currently experimental and is likely
to change, or even change drastically.

Symfony UX Toolkit provides a set of ready-to-use kits for Symfony applications.
It is part of `the Symfony UX initiative`_.

Kits are a nice way to begin a new Symfony application, by providing a set
of `Twig components`_ (based on Tailwind CSS, but fully customizable depending
on your needs).

Please note that the **UX Toolkit is not a library of UI components**,
but **a tool to help you build your own UI components**.
It uses the same approach than the popular `Shadcn UI`_,
and a similar approach than `Tailwind Plus`_.

After installing the UX Toolkit, you can start pulling the components you need
from the `UX Components page`_, and use them in your project.
They become your own components, and you can customize them as you want.

Additionally, some `Twig components`_ use ``html_cva`` and ``tailwind_merge``,
you can either remove them from your project or install ``twig/html-extra``
and ``tales-from-a-dev/twig-tailwind-extra`` to use them.

Also, we do not force you to use Tailwind CSS at all. You can use whatever
CSS framework you want, but you will need to adapt the UI components to it.

Installation
------------

Install the UX Toolkit using Composer and Symfony Flex:

.. code-block:: terminal

    # The UX Toolkit is a development dependency:
    $ composer require --dev symfony/ux-toolkit

    # If you want to keep `html_cva` and `tailwind_merge` in your Twig components:
    $ composer require twig/extra-bundle twig/html-extra:^3.12.0 tales-from-a-dev/twig-tailwind-extra

Configuration
-------------

Configuration is done in your ``config/packages/ux_toolkit.yaml`` file:

.. code-block:: yaml

    # config/packages/ux_toolkit.yaml
    ux_toolkit:
        kit: 'shadcn'

Usage
-----

You may find a list of components in the `UX Components page`_, with the installation instructions for each of them.

For example, if you want to install the `Button` component, you will find the following instruction:

.. code-block:: terminal

    $ php bin/console ux:toolkit:install-component Button

It will create the ``templates/components/Button.html.twig`` file, and you will be able to use the `Button` component like this:

.. code-block:: html+twig

    <twig:Button>Click me</twig:Button>

Create your own kit
-------------------

You have the ability to create and share your own kit with the community,
by using the ``php vendor/bin/ux-toolkit-kit-create`` command in a new GitHub repository:

.. code-block:: terminal

    # Create your new project
    $ mkdir my-ux-toolkit-kit
    $ cd my-ux-toolkit-kit

    # Initialize your project
    $ git init
    $ composer init

    # Install the UX Toolkit
    $ composer require --dev symfony/ux-toolkit

    # Create your kit
    $ php vendor/bin/ux-toolkit-kit-create

    # ... edit the files, add your components, examples, etc.

    # Share your kit
    $ git add .
    $ git commit -m "Create my-kit UX Toolkit"
    $ git branch -M main
    $ git remote add origin git@github.com:my-username/my-ux-toolkit-kit.git
    $ git push -u origin main

Repository and kits structure
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

After creating your kit, the repository should have the following structure:

.. code-block:: text

    .
    ├── docs
    │   └── components
    │       └── Button.twig
    ├── manifest.json
    └── templates
        └── components
            └── Button.html.twig

A kit is composed of:

- A ``manifest.json`` file, that describes the kit (name, license, homepage, authors, ...),
- A ``templates/components`` directory, that contains the Twig components,
- A ``docs/components`` directory, optional, that contains the documentation for each "root" Twig component.

Use your kit in a Symfony application
-------------------------------------

You can globally configure the kit to use in your application by setting the ``ux_toolkit.kit`` configuration:

.. code-block:: yaml

    # config/packages/ux_toolkit.yaml
    ux_toolkit:
        kit: 'github.com/my-username/my-ux-kits'
        # or for a specific version
        kit: 'github.com/my-username/my-ux-kits:1.0.0'

If you do not want to globally configure the kit, you can pass the ``--kit`` option to the ``ux:toolkit:install-component`` command:

.. code-block:: terminal

    $ php bin/console ux:toolkit:install-component Button --kit=github.com/my-username/my-ux-kits

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

However, the UI components and other files provided by the Toolkit **are not** covered by the Backward Compatibility
promise.
We may break them in patch or minor release, but you won't get impacted unless you re-install the same UI component.

.. _`the Symfony UX initiative`: https://ux.symfony.com/
.. _`Twig components`: https://symfony.com/bundles/ux-twig-component/current/index.html
.. _`UX Components page`: https://ux.symfony.com/components
.. _`Shadcn UI`: https://ui.shadcn.com/
.. _`Tailwind Plus`: https://tailwindcss.com/plus
