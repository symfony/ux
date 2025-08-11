Symfony UX Toolkit
==================

**EXPERIMENTAL** This component is currently experimental and is likely
to change, or even change drastically.

Symfony UX Toolkit provides a set of ready-to-use kits for Symfony applications.
It is part of `the Symfony UX initiative`_.

Kits are a nice way to begin a new Symfony application, they contains
recipes to install nicely-crafter `Twig components`_ (already stylized,
but fully customizable depending on your needs) and more.

Please note that the **UX Toolkit is not a library of UI components**,
but **a tool to help you build your own UI components**.
It uses the same approach than the popular `Shadcn UI`_,
and a similar approach than `Tailwind Plus`_.

After installing the UX Toolkit, you can start installing the recipes you need
from `UX Toolkit Kits`_ and use them in your project.
Files created by the recipes become part of your project, and
you can customize them as you want.

Additionally, some `Twig components`_ use ``html_cva`` and ``tailwind_merge``,
you can either remove them from your project or install ``twig/html-extra``
and ``tales-from-a-dev/twig-tailwind-extra`` to use them.

Installation
------------

Install the UX Toolkit using Composer and Symfony Flex:

.. code-block:: terminal

    # The UX Toolkit is a development dependency:
    $ composer require --dev symfony/ux-toolkit

Usage
-----

You may find a list of available kits in the `UX Toolkit Kits`_ page, with the installation instructions for each of them.

For example, if you want to install a `Button` component, you will find the following instruction:

.. code-block:: terminal

    $ php bin/console ux:install Button --kit=<kitName>

It will create the ``templates/components/Button.html.twig`` file in your project,
and you will be able to use the `Button` component like this:

.. code-block:: html+twig

    <twig:Button>Click me</twig:Button>

Create your own Kit
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
    ├── Button
    │   ├── manifest.json
    │   └── templates
    │       └── components
    │           └── Button.html.twig
    └── manifest.json


A kit is described by a ``manifest.json`` file at the root directory, which contains the metadata of the kit:

.. code-block:: json

    {
        "$schema": "../vendor/symfony/ux-toolkit/schema-kit-v1.json",
        "name": "My UX Toolkit Kit",
        "description": "A custom kit for Symfony UX Toolkit.",
        "homepage": "https://github/com/User/MyUxToolkitKit",
        "license": "MIT"
    }

Then, a kit can contain one or more recipes. Each recipe is a directory
with a ``manifest.json`` file and some files to be copied into the project.

The ``manifest.json`` file of a recipe contains the metadata of the recipe:

.. code-block:: json

    {
        "$schema": "../vendor/symfony/ux-toolkit/schema-kit-recipe-v1.json",
        "name": "Button",
        "description": "A clickable element that triggers actions or events, supporting various styles and states.",
        "copy-files": {
            "templates/": "templates/"
        },
        "dependencies": {
            {
                "type": "php",
                "package": "twig/extra-bundle"
            },
            {
                "type": "php",
                "package": "twig/html-extra:^3.12.0"
            },
            {
                "type": "php",
                "package": "tales-from-a-dev/twig-tailwind-extra"
            }
        }
    }

Using your kit
~~~~~~~~~~~~~~

Once your kit is published on GitHub, you can use it by specifying the ``--kit`` option when installing a component:

.. code-block:: terminal

    $ php bin/console ux:install Button --kit=github.com/my-username/my-ux-toolkit-kit

    # or for a specific version
    $ php bin/console ux:install Button --kit=github.com/my-username/my-ux-toolkit-kit:1.0.0

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
.. _`UX Toolkit Kits`: https://ux.symfony.com/toolkit#kits
.. _`Shadcn UI`: https://ui.shadcn.com/
.. _`Tailwind Plus`: https://tailwindcss.com/plus
