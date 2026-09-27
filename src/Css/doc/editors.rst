Editors and static analysis
===========================

The type of the css() hash
--------------------------

In debug mode, the bundle writes ``config/reference_css.php``: the PHPDoc
types of the hash that ``css()`` takes, built from your tokens, conditions
and breakpoints. It is rewritten only when its content changes, like
``config/reference.php``. Commit it, so the types are there on every
machine.

The file declares a ``CssStyles`` array shape on an empty
``Symfony\UX\Css\CssReference`` class::

    // config/reference_css.php
    namespace Symfony\UX\Css;

    /**
     * @psalm-type ColorsToken = 'blue.500'|'primary'|string
     * @psalm-type CssStyles = array{
     *     color?: ColorsToken|array<ColorsToken|array<array-key, mixed>>,
     *     p?: SpacingToken|array<SpacingToken|array<array-key, mixed>>,
     *     _hover?: array<string, mixed>,
     *     md?: array<string, mixed>,
     *     ...
     * }
     */
    final class CssReference
    {
    }

Each property lists the tokens or keywords it accepts, and each condition and
breakpoint is a key. The hash under a condition or a breakpoint is not typed:
PHPStan refuses a type that refers to itself, and writing out each nested
level adds minutes to its analysis.

``string`` stays in every union. Values written in a template are checked when
Twig compiles it, and values only known at runtime are checked in debug mode:
see :doc:`validation`.

In your editor
--------------

The ``css()`` function declares its parameter with ``CssStyles``. Editors that
read PHPDoc array shapes can use it:

* PhpStorm completes the keys of an array shape in PHP code;
* in Twig templates, neither the Symfony plugin for PhpStorm nor Symfony
  Language Tools completes the keys of a hash from an array shape yet.

Static analysis
---------------

A ``css()`` call written in a template is compiled into a string of classes,
so PHPStan, or TwigStan on compiled templates, has nothing left to check there:
Twig itself reports the mistakes, in ``lint:twig`` for example. Values only
known at runtime are checked by ``css()`` in debug mode. See :doc:`validation`.

PHP code that calls the ``css()`` runtime directly is the one place where
PHPStan uses the types. Since ``config/`` is not autoloaded, add the file to
its ``scanFiles``:

.. code-block:: yaml

    # phpstan.neon
    parameters:
        scanFiles:
            - config/reference_css.php
