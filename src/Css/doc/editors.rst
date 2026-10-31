Editors and static analysis
===========================

The type of the css() hash
--------------------------

In debug mode, the bundle writes ``config/reference_css.php``: the PHPDoc
types of the hash that ``css()`` takes, built from your tokens, conditions
and breakpoints. It is rewritten only when its content changes, like
``config/reference.php``. Commit it, so the types are there on every
machine.

The file declares a ``CssStyles`` array shape on the
``Symfony\UX\Css\CssReference`` class, and the values of your tokens in its
``TOKENS`` constant::

    // config/reference_css.php
    namespace Symfony\UX\Css;

    /**
     * @psalm-type ColorsToken = 'blue.500'|'primary'|string
     * @psalm-type CssStyles = array{
     *     color?: ColorsToken|array<ColorsToken|array<array-key, mixed>>,
     *     p?: SpacingToken|array<SpacingToken|array<array-key, mixed>>,
     *     _hover?: CssStylesNested,
     *     md?: CssStylesNested,
     *     ...
     * }
     * @psalm-type CssStylesNested = array{
     *     color?: ColorsToken|array<ColorsToken|array<array-key, mixed>>,
     *     p?: SpacingToken|array<SpacingToken|array<array-key, mixed>>,
     *     _hover?: array<string, mixed>,
     *     md?: array<string, mixed>,
     *     ...
     * }
     */
    final class CssReference
    {
        public const TOKENS = [
            'colors' => [
                'blue.500' => '#3b82f6',
                'primary' => '#3b82f6',
            ],
            'spacing' => [
                'md' => '1rem',
                '-md' => '-1rem',
            ],
        ];
    }

Each property lists the tokens or keywords it accepts, and each condition and
breakpoint is a key. The hash under a condition or a breakpoint is typed one
level deep, with ``CssStylesNested``. Below that it is not typed: PHPStan
refuses a type that refers to itself, and each nested level slows its analysis
down.

``string`` stays in every union. Values written in a template are checked when
Twig compiles it, and values only known at runtime are checked in debug mode:
see :doc:`validation`.

``TOKENS`` maps each token to its value, by category, so that editors can show
the value of a token: the color of a color token, the length of a spacing token.
A semantic token gets the final value of the token it refers to: ``primary``
refers to ``{colors.blue.500}``, so it gets ``#3b82f6``. A conditional token
gets its ``base`` value. A negative token gets the negative value: ``-md`` gets
``-1rem``.

In your editor
--------------

The ``css()`` function declares its parameter with ``CssStyles``. Editors that
read PHPDoc array shapes can use it:

* PhpStorm completes the keys of an array shape in PHP code;
* in Twig templates, the Symfony plugin for PhpStorm completes the keys and
  values of a ``css()`` hash since version 2026.2.314;
* Symfony Language Tools does the same since version 0.24.0, when it can run
  its dynamic analysis of the project.

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
