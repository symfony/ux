Symfony UX Toolkit
==================

**EXPERIMENTAL** This component is currently experimental and is likely
to change, or even change drastically.

Symfony UX Toolkit provides a set of ready-to-use Tailwind CSS based UI components for Symfony applications.
It is part of `the Symfony UX initiative`_.

Please note that the **UX Toolkit is not a library of UI components**,
but **a tool to help you build your own UI components**.
It uses the same approach than the popular `Shadcn UI`_,
and a similar approach than `Tailwind Plus`_.

After installing the UX Toolkit, you can start pulling the components you need
from the `UX Components page`_, and use them in your project.
They become your own components, and you can customize them as you want.

These UI components are based on `Twig Component`_ UX package, so we are sure
to provide the best experience as possible to Symfony developers.

Additionally, the UI components come with `html_cva` and `tailwind_merge`,
you can either remove them from your project or install `twig/html-extra`
and `tales-from-a-dev/twig-tailwind-extra` to use them.

Also, we do not force you to use Tailwind CSS at all. If you want to use
another CSS framework, you can, but you will need to adapt the UI components to it.

Installation
------------

Install the UX Toolkit using Composer and Symfony Flex:

.. code-block:: terminal

    # The UX Toolkit is a development dependency:
    $ composer require symfony/ux-toolkit --dev

    # If you want to to keep `html_cva` and `tailwind_merge` in your UI components:
    $ composer require twig/extra-bundle twig/html-extra:^3.12.0 tales-from-a-dev/twig-tailwind-extra

Configuration
-------------

TODO

Usage
-----

You may find a list of components in the `UX Components page`_, with the installation instructions for each of them.

For example, if you want to install the `Button` component, you will find the following instruction:

.. code-block:: terminal

    $ bin/console ux:toolkit:install Button

It will create the ``templates/components/Button.html.twig`` file, and you will be able to use the `Button` component like this:

.. code-block:: html+twig

    <twig:Button>Click me</twig:Button>

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

However, the UI components and other files provided by the Toolkit **are not** covered by the Backward Compatibility
promise.
We may break them in patch or minor release, but you won't get impacted unless you re-install the same UI component.

.. _`the Symfony UX initiative`: https://ux.symfony.com/
.. _`Twig Component`: https://symfony.com/bundles/ux-twig-component/current/index.html
.. _`UX Components page`: https://ux.symfony.com/components
.. _`Shadcn UI`: https://ui.shadcn.com/
.. _`Tailwind Plus`: https://tailwindcss.com/plus
