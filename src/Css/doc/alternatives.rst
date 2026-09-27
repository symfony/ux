Compared to other CSS tools
===========================

``css()`` is one of several ways to style Twig templates. This page explains
how it differs from the tools you may already use, and what those tools do
better. The facts about other tools come from their documentation, as of
September 2026.

Tailwind CSS
------------

`Tailwind CSS`_ generates utility classes for the class names it finds in your
files. It reads the files as plain text, so:

* a misspelled class, like ``bg-blu-500``, gets no CSS, and nothing reports
  it;
* a class name built from pieces, like ``text-{{ color }}-600``, is not found,
  and gets no CSS either.

With ``css()``, styles are a hash that Twig parses. A misspelled property,
token or keyword is a Twig syntax error that points to the line, and
``lint:twig`` catches it in CI. Using a value that comes from a variable is
supported: see :doc:`dynamic-styles`.

Tailwind declares design tokens in CSS, with the ``@theme`` directive, and
exposes them as CSS variables. ``css()`` declares them in the bundle
configuration, which is checked when the container is built, and exposes all
of them as CSS variables too.

Tailwind needs a build step. Its standalone CLI runs without Node.js, and the
`TailwindBundle`_ of SymfonyCasts runs it for AssetMapper. With ``css()``, PHP
writes the CSS file: there is no binary to download and no watcher to start.

What Tailwind does better:

* editor support: the Tailwind CSS IntelliSense extension for VS Code, and
  the Tailwind CSS plugin of PhpStorm, complete class names and preview their
  CSS;
* a large ecosystem of documentation and ready-made components, including
  the Shadcn and Flowbite kits of Symfony UX Toolkit;
* short class names that work in any file, including JavaScript.

UnoCSS
------

`UnoCSS`_ is an atomic CSS engine. It generates the utilities it finds, from
presets: one of them follows the class names of Tailwind CSS, and another one
lets you write utilities as HTML attributes, like ``bg="blue-400"``. It runs
through Vite, Webpack, PostCSS or its CLI, which need Node.js. It can also run
through a runtime that generates the CSS in the browser.

``css()`` produces the same kind of atomic CSS without Node.js, and checks the
styles when Twig compiles the template.

What UnoCSS does better: its rules and presets are fully customizable, and it
works with any template language and any JavaScript framework.

Bootstrap utilities
-------------------

`Bootstrap`_ ships utility classes for spacing, display, flexbox, colors and
more, generated from Sass by its utility API. Only some utilities have
responsive variants, like ``.d-md-none``, and variants for states such as
``:hover`` must be enabled utility by utility. Adding or changing a utility
means compiling Bootstrap's Sass. Colors and other values are CSS variables
with the ``--bs-`` prefix.

With ``css()``, every property works with every condition and breakpoint, and
the tokens are declared in YAML, without Sass.

What Bootstrap does better: its components, such as navbars, modals and
dropdowns, their JavaScript, and their accessible defaults.

Both can live in the same page. Bootstrap's CSS is not in a cascade layer, so
it wins over ``css()`` classes on the same property: see
:doc:`css-function` to change that.

Hand-written CSS
----------------

Plain CSS, with custom properties for the design values, needs no tool and
gives full control.

With ``css()``, the bundle writes the rules, once per style, for every
template. The file is built from the templates, so a style that no template
uses any more disappears from it. And since the tokens are CSS variables, both
work together: ``css()`` for most elements, and hand-written CSS with
``var(--colors-primary)`` for the rest.

What hand-written CSS does better: complex selectors, ``@keyframes``, and
styles for HTML you do not write yourself, such as the output of a Markdown
parser or a third-party widget.

Panda CSS
---------

`Panda CSS`_ generates atomic CSS from ``css()`` calls in JavaScript and
TypeScript, and checks them with generated TypeScript types. For the same
configuration, ``css()`` gives the same class names and the same CSS as Panda.
:doc:`panda` explains what differs.

.. _`Tailwind CSS`: https://tailwindcss.com/
.. _`TailwindBundle`: https://symfony.com/bundles/TailwindBundle/current/index.html
.. _`UnoCSS`: https://unocss.dev/
.. _`Bootstrap`: https://getbootstrap.com/
.. _`Panda CSS`: https://panda-css.com/
