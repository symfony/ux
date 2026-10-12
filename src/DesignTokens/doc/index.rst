Symfony UX Design Tokens
========================

.. caution::

    **EXPERIMENTAL** This bundle is currently experimental and is likely to
    change, possibly significantly, before its first stable release.

A design token is a named design decision, such as a brand color or a spacing
step. This bundle reads tokens written in the `DTCG 2025.10`_ format of the
W3C Design Tokens Community Group, and hands their resolved values to CSS,
Twig, PHP and other build tools.

* **One source.** A brand color used in a stylesheet, a template, an email and
  a design file lives in the token file, and everything else reads it.
* **Values, not strings.** In PHP, a token keeps its DTCG structure: a color
  has a color space and components. CSS is one projection among several.
* **Resolution on the server.** A theme per user, brand or tenant is a Resolver
  input, not a rebuild.

Installation
------------

.. code-block:: terminal

    $ composer require symfony/ux-design-tokens

With Symfony Flex, the bundle is registered for you. Without Flex, register it
in ``config/bundles.php``::

    // config/bundles.php
    return [
        // ...
        Symfony\UX\DesignTokens\UXDesignTokensBundle::class => ['all' => true],
    ];

Writing a token file
--------------------

A DTCG file is JSON: a tree of groups, where a node with ``$value`` is a token
and its path is its position in the tree. Values are objects, not CSS strings:

.. code-block:: json

    {
        "color": {
            "palette": {
                "$type": "color",
                "blue-500": { "$value": { "colorSpace": "oklch", "components": [0.62, 0.18, 255] } },
                "slate-900": { "$value": { "colorSpace": "oklch", "components": [0.24, 0.02, 255] } }
            },
            "action": {
                "primary": { "$value": "{color.palette.blue-500}" }
            },
            "content": {
                "default": { "$value": "{color.palette.slate-900}" }
            }
        },
        "dimension": {
            "$type": "dimension",
            "radius": { "control": { "$value": { "value": 0.375, "unit": "rem" } } },
            "spacing": { "md": { "$value": { "value": 1, "unit": "rem" } } }
        }
    }

``$type`` set on a group applies to every token under it.
``color.action.primary`` is an alias: it names a role and points at a palette
token instead of repeating its value. A ``$ref`` JSON Pointer can also address
a token, a group or a member of a value, in the same file or in another local
file. Cycles, missing targets and values that do not match their type fail
with the path of the token.

The bundle supports the 13 DTCG token types: ``color``, ``dimension``,
``number``, ``duration``, ``fontFamily``, ``fontWeight``, ``cubicBezier``,
``strokeStyle``, ``border``, ``transition``, ``shadow``, ``gradient`` and
``typography``. The last six are composite values made of the others.

Keep token files outside ``assets/``, since AssetMapper publishes every file
under that directory. The examples use a ``design/`` directory at the project
root.

Starting from a design tool
~~~~~~~~~~~~~~~~~~~~~~~~~~~

Tokens Studio exports DTCG. Figma exports a variable collection as DTCG from
the context menu of the collection in the variables panel, with these limits:

* one file per mode, and nothing that ties the modes together;
* an alias is exported as the value it points to; the link survives only in
  ``$extensions.com.figma.aliasData``;
* a number variable is a ``number`` without unit, even for a spacing or a
  radius;
* a string variable has the ``string`` type, which DTCG does not define, so the
  file fails validation until that variable is removed;
* numbers keep the 32-bit precision Figma stores, such as
  ``0.699999988079071`` for ``0.7``.

A Resolver document then ties the files together: a collection with one mode
becomes a set, a collection with several modes a modifier. See
`Themes with a Resolver`_.

A Tailwind CSS v4 theme converts to DTCG with ``ux:design-tokens:import``; see
`Importing a Tailwind theme`_.

Configuration
-------------

.. code-block:: yaml

    # config/packages/ux_design_tokens.yaml
    ux_design_tokens:
        paths:
            - '%kernel.project_dir%/design/base.tokens.json'
            - '%kernel.project_dir%/design/brand.tokens.json'
        color_scheme:
            modifier: scheme
            light: light
            dark: dark
        css_prefix: dt

========================= ================ =========== ===============================
Option                    Type             Default     Purpose
========================= ================ =========== ===============================
``paths``                 list of strings  ``[]``      Token files, merged in order
``resolver.path``         string or null   ``null``    Resolver document
``resolver.inputs``       map              ``{}``      Context selected per modifier
``color_scheme.modifier`` string           ``scheme``  Modifier of the color schemes
``color_scheme.light``    string           ``light``   Context written to ``:root``
``color_scheme.dark``     string           ``dark``    Context written for dark
``css_prefix``            string           ``dt``      Prefix of the CSS variables
========================= ================ =========== ===============================

The bundle reads nothing until ``paths`` or ``resolver.path`` is set.

Files in ``paths`` are merged in order: a later file overrides the tokens it
redefines and keeps the others. Aliases are resolved after the merge, so an
alias sees the final value. A list value, such as a ``fontFamily``, is
replaced as a whole.

A path is absolute or relative to the project directory, and may use container
parameters. Every configured file is tracked, so editing it rebuilds the
container. Environment variables work in paths and Resolver inputs:

.. code-block:: yaml

    # config/packages/ux_design_tokens.yaml
    ux_design_tokens:
        resolver:
            path: '%env(resolve:DESIGN_TOKENS_RESOLVER)%'
            inputs:
                scheme: '%env(DESIGN_TOKENS_SCHEME)%'

.. caution::

    A path built from an environment variable must be **absolute**, or start
    with ``%kernel.project_dir%``: its value is unknown while the container
    compiles. Such a file is not tracked either, so run ``cache:clear`` after
    changing it.

A ``$ref`` can point at another document, and that document is read as data.
Reads are confined to the project directory and the directories of the
configured files, symlinks resolved. To read documents from elsewhere, such as
an object store, decorate the service aliased on
``Symfony\UX\DesignTokens\Resolver\DocumentLoaderInterface``.

Each resolution is stored in the system cache the first time it is needed, and
``cache:clear`` empties it. In debug mode, a resolution is computed again when
one of the documents it read changes.

Rendering the tokens as CSS
---------------------------

With TwigBundle installed, call ``ux_token_css()`` once in the document head:

.. code-block:: twig

    {# templates/base.html.twig #}
    {% block stylesheets %}
        {{ ux_token_css() }}
    {% endblock %}

It returns a complete ``<style>`` element; do not wrap it in another one. The
file above renders:

.. code-block:: html

    <style>
    @property --dt-color-palette-blue-500 { syntax: '\3C color>'; inherits: true; initial-value: oklch(62% 0.18 255); }
    @property --dt-color-palette-slate-900 { syntax: '\3C color>'; inherits: true; initial-value: oklch(24% 0.02 255); }
    @property --dt-color-action-primary { syntax: '\3C color>'; inherits: true; initial-value: oklch(62% 0.18 255); }
    @property --dt-color-content-default { syntax: '\3C color>'; inherits: true; initial-value: oklch(24% 0.02 255); }

    :root {
      --dt-color-palette-blue-500: oklch(62% 0.18 255);
      --dt-color-palette-slate-900: oklch(24% 0.02 255);
      --dt-color-action-primary: oklch(62% 0.18 255);
      --dt-color-content-default: oklch(24% 0.02 255);
      --dt-dimension-radius-control: 0.375rem;
      --dt-dimension-spacing-md: 1rem;
    }
    </style>

Stylesheets read the variables and never mention the token file:

.. code-block:: css

    /* assets/styles/app.css */
    .button {
        background: var(--dt-color-action-primary);
        color: var(--dt-color-content-default);
        border-radius: var(--dt-dimension-radius-control);
        padding: var(--dt-dimension-spacing-md);
    }

A few rules shape the output:

* Aliases are resolved first: ``--dt-color-action-primary`` holds the value,
  not a ``var()`` chain.
* Color, duration, number and ``px`` dimension tokens get an ``@property``
  rule, which types the variable and makes it animatable. A ``rem`` dimension
  gets none, since an ``@property`` initial value must not depend on a font
  size.
* ``\3C`` is the CSS escape of ``<``, so no token value can close the
  ``<style>`` element.
* Dots in a path become hyphens, after the ``css_prefix``. Two paths that give
  the same variable name fail the rendering.
* A typography, border, shadow or transition token writes its composite value
  and one variable per member: ``--dt-typography-heading`` comes with
  ``--dt-typography-heading-font-size`` and the other members. A shadow with
  several layers numbers them from 1.

Light and dark
~~~~~~~~~~~~~~

Light and dark are CSS concepts: DTCG only knows Resolver modifiers and their
contexts. The ``color_scheme`` option names the modifier and its two contexts,
``scheme`` with ``light`` and ``dark`` by default:

.. code-block:: yaml

    # config/packages/ux_design_tokens.yaml
    ux_design_tokens:
        resolver:
            path: '%kernel.project_dir%/design/theme.resolver.json'
        color_scheme:
            modifier: mode
            light: day
            dark: night

When the Resolver declares that modifier with both contexts, and
``resolver.inputs`` does not select one of them, the stylesheet holds both
schemes. The light context fills ``:root``, and only the variables whose value
changes in the dark context follow:

.. code-block:: html

    <style>
    :root {
      --dt-color-content-default: color(srgb 0.1294 0.1451 0.1608);
      --dt-color-surface-canvas: color(srgb 1 1 1);
      --dt-dimension-spacing-md: 1rem;
    }

    @media (prefers-color-scheme: dark) {
      :root:not([data-theme="light"]):not([data-theme="dark"]) {
        --dt-color-content-default: color(srgb 0.8706 0.8863 0.902);
        --dt-color-surface-canvas: color(srgb 0.1294 0.1451 0.1608);
      }
    }

    :root[data-theme="dark"] {
      --dt-color-content-default: color(srgb 0.8706 0.8863 0.902);
      --dt-color-surface-canvas: color(srgb 0.1294 0.1451 0.1608);
    }
    </style>

The page follows the operating system until ``data-theme="light"`` or
``data-theme="dark"`` is set on ``<html>``. The comparison runs variable by
variable, after composite tokens are split: a border whose color alone changes
writes its value and its color, not its width.

Linking a stylesheet instead of inlining it
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

``ux_token_css()`` writes the CSS into every response. When the stylesheet is
the same for every visitor, ``ux_token_stylesheet()`` links it instead, so the
browser caches it. It needs AssetMapper:

.. code-block:: twig

    {# templates/base.html.twig #}
    {{ ux_token_stylesheet() }}

.. code-block:: html

    <link rel="stylesheet" href="/assets/design-tokens/tokens-CuTbN4D.css">

The file lives in the build directory. ``cache:warmup`` writes it, a debug
kernel writes it again when a source changes, and ``asset-map:compile`` picks
it up like any other asset. ``ux_token_css()`` inlines the same file while it
is up to date.

To produce the CSS in PHP, without the ``<style>`` element, call the
generator::

    // src/Controller/TokenStylesheetController.php
    namespace App\Controller;

    use Symfony\Component\HttpFoundation\Response;
    use Symfony\UX\DesignTokens\Generator\CssGenerator;
    use Symfony\UX\DesignTokens\TokenRegistryInterface;

    final class TokenStylesheetController
    {
        public function __invoke(TokenRegistryInterface $tokens): Response
        {
            return new Response(new CssGenerator('dt')->generate($tokens->all()), headers: [
                'Content-Type' => 'text/css; charset=utf-8',
            ]);
        }
    }

Reading a token
---------------

Custom properties do not reach everywhere: an email client, a PDF, an SVG
attribute, or PHP code that needs the value itself. ``ux_token()`` returns one
token, and printing it gives its CSS value:

.. code-block:: html+twig

    {# templates/email/welcome.html.twig #}
    <h1 style="color: {{ ux_token('color.action.primary') }}">Welcome</h1>

Here it prints ``oklch(62% 0.18 255)``. A missing path fails the render.

In PHP, inject ``TokenRegistryInterface``::

    // src/Mailer/BrandMailer.php
    namespace App\Mailer;

    use Symfony\UX\DesignTokens\TokenRegistryInterface;

    final class BrandMailer
    {
        public function __construct(private readonly TokenRegistryInterface $tokens)
        {
        }

        public function brandColor(): string
        {
            // "oklch(62% 0.18 255)"
            return (string) $this->tokens->get('color.action.primary');
        }
    }

Casting to a string is the output edge. Before it, the token keeps its DTCG
value::

    $token = $this->tokens->get('color.action.primary');

    $token->getType();  // "color"
    $token->getValue(); // ['colorSpace' => 'oklch', 'components' => [0.62, 0.18, 255]]

A token also exposes ``getDescription()``, ``getExtensions()``,
``isDeprecated()`` and ``getDeprecationMessage()``. The registry offers:

============================ ==============================================================
Method                       Returns
============================ ==============================================================
``get($path)``               The token, or throws ``TokenNotFoundException``
``find($path)``              The token, or ``null``
``has($path)``               Whether the path names a token, not a group
``all()``                    The nested tree of resolved tokens
``flatten()``                Every token, keyed by its dot-notation path
``getModifiers()``           The Resolver modifiers, with their contexts and default
``getPermutations()``        Every combination of inputs the Resolver can produce
============================ ==============================================================

``TokenNotFoundException::getPath()`` returns the path that did not resolve.

Themes with a Resolver
----------------------

A DTCG token holds one value. A theme is another file, and a Resolver document
says which files to combine for a given context:

.. code-block:: json

    {
        "version": "2025.10",
        "sets": {
            "foundation": { "sources": [{ "$ref": "brand.tokens.json" }] }
        },
        "modifiers": {
            "scheme": {
                "contexts": {
                    "light": [{ "$ref": "scheme-light.tokens.json" }],
                    "dark": [{ "$ref": "scheme-dark.tokens.json" }]
                },
                "default": "light"
            }
        },
        "resolutionOrder": [
            { "$ref": "#/sets/foundation" },
            { "$ref": "#/modifiers/scheme" }
        ]
    }

A set is always included. A modifier includes the sources of one of its
contexts. The selected sources are merged in ``resolutionOrder``, later ones
winning, and aliases are resolved after the merge: when
``scheme-dark.tokens.json`` overrides the palette, every alias pointing at it
follows.

Point the bundle at the document, and select contexts:

.. code-block:: yaml

    # config/packages/ux_design_tokens.yaml
    ux_design_tokens:
        resolver:
            path: '%kernel.project_dir%/design/theme.resolver.json'
            inputs:
                scheme: dark

A modifier with a ``default`` uses it when no input selects a context; a
modifier without one requires an input. Modifier and context names are
case-insensitive. An unknown modifier or context raises a
``ResolverException`` that lists every problem at once.

The configured inputs are defaults. Twig functions and registry methods accept
other inputs for one call, and keep the configured value for every modifier
the call does not name:

.. code-block:: twig

    {# templates/package/show.html.twig #}
    {{ ux_token_css({brand: package.brand}) }}

    <meta name="theme-color" content="{{ ux_token('color.action.primary', {brand: package.brand}) }}">

.. code-block:: php

    $accent = $tokens->get('color.action.primary', ['brand' => 'sky', 'scheme' => 'dark']);

    $tokens->getModifiers();
    // [
    //     'brand' => ['contexts' => ['symfony', 'sky'], 'default' => 'symfony'],
    //     'scheme' => ['contexts' => ['light', 'dark'], 'default' => 'light'],
    // ]

Inputs that name the color scheme, such as ``{scheme: 'dark'}``, render that
one context under ``:root``.

Exporting and importing
-----------------------

Rendering per request suits an application that changes theme per user. When
the output is static, or another tool needs the tokens, export them:

.. code-block:: terminal

    $ php bin/console ux:design-tokens:export css public/build/tokens.css
    $ php bin/console ux:design-tokens:export javascript assets/generated/tokens.js
    $ php bin/console ux:design-tokens:export dtcg build/tokens.tokens.json
    $ php bin/console ux:design-tokens:export tailwind assets/styles/tokens.theme.css
    $ php bin/console ux:design-tokens:export design.md DESIGN.md --title="Acme"

Without an output path, the result goes to standard output. Every format is
generated from the resolved tokens, so none keeps the authoring structure of
the source files:

============== ================================ =========================================================
Format         For                              Keeps
============== ================================ =========================================================
``css``        Browsers                         Final values, light and dark as in ``ux_token_css()``
``javascript`` Browser and Node.js code         Final CSS values by path, in a dependency-free module
``dtcg``       Other DTCG tools, a handoff      Structured values, types and metadata; aliases resolved
``tailwind``   Tailwind CSS v4 utilities        The types Tailwind has a namespace for
``design.md``  Google DESIGN.md, in alpha       Colors, typography, spacing, radii; the rest as tables
============== ================================ =========================================================

The export uses the configured resolution. ``--input`` selects another
context, once per modifier, and ``--all-permutations`` writes one file per
combination, inserting the inputs before the extension:

.. code-block:: terminal

    $ php bin/console ux:design-tokens:export css build/dark.css --input=scheme=dark
    $ php bin/console ux:design-tokens:export css build/theme.css --all-permutations

With the Resolver above, the second command writes ``build/theme.scheme-light.css``
and ``build/theme.scheme-dark.css``. ``--css-prefix`` overrides ``css_prefix``
for one CSS export.

JavaScript
~~~~~~~~~~

The module exports a frozen ``tokens`` object and a ``token()`` function that
throws on an unknown path:

.. code-block:: javascript

    import { token, tokens } from './generated/tokens.js';

    document.documentElement.style.setProperty('--preview-accent', token('color.action.primary'));
    console.log(tokens['dimension.spacing.md']); // "1rem"

Tailwind CSS
~~~~~~~~~~~~

The export writes an ``@theme static`` block, to load after Tailwind:

.. code-block:: css

    /* assets/styles/app.css */
    @import "tailwindcss";
    @import "./tokens.theme.css";

A variable goes to the Tailwind namespace of its type: ``--color-*``,
``--font-*``, ``--font-weight-*``, ``--ease-*``, ``--shadow-*``. A dimension or
a number goes to the namespace its path names: ``dimension.radius.control``
becomes ``--radius-control``, ``font.size.body`` becomes ``--text-body``, and a
dimension with no recognized segment goes to ``--spacing-*``. Types Tailwind
has no namespace for are left out.

DESIGN.md
~~~~~~~~~

The ``design.md`` format needs ``symfony/yaml``. DESIGN.md colors do not accept
the CSS ``color()`` function, so an ``srgb`` color is written as ``rgb()``,
other CSS color spaces with their own function, and any other space with its
``hex`` fallback. A color with none of these fails the export. DESIGN.md is an
alpha format: lint the generated file with the Google CLI version the project
pins, ``npx @google/design.md lint DESIGN.md``.

Importing a Tailwind theme
~~~~~~~~~~~~~~~~~~~~~~~~~~

A Tailwind CSS v4 ``@theme`` block converts to DTCG:

.. code-block:: terminal

    $ php bin/console ux:design-tokens:import tailwind assets/styles/theme.css \
        design/tailwind.tokens.json

The importer reads every top-level ``@theme`` block, so the ``theme.css``
Tailwind ships imports as it is. An exact ``var(--other)`` becomes a DTCG
alias when its target is in the same file. Names stay flat inside their
namespace: ``--color-brand-primary`` becomes ``color.brand-primary``.

Variables DTCG cannot represent are left out and logged to standard error, so
a document written to standard output stays valid JSON:

* a value DTCG cannot hold, such as ``calc()`` or a length in ``em``, is a
  warning, always shown;
* a variable DTCG has no type for, such as an animation, is a notice, shown
  with ``-v``.

Pass ``--force`` to replace an existing output file.

Checking the files
------------------

``lint:design-tokens`` validates token and Resolver documents. Without
arguments, it lints the configured files, then checks that the application's
own resolution succeeds:

.. code-block:: terminal

    $ php bin/console lint:design-tokens
    $ php bin/console lint:design-tokens design/
    $ php bin/console lint:design-tokens design/theme.resolver.json --format=json

A Resolver document is checked in every permutation, so an alias that breaks
only in a context nobody configured still fails. On GitHub Actions, the output
defaults to annotations (``--format=github``).

In a layered theme, a file can alias a token that another file defines:
``design/foundation.tokens.json`` points at ``color.palette.brand``, which only
the brand files define. When the same run lints the Resolver that
uses such a file, the file is checked for everything but those references, and
the Resolver checks them. Lint the directory, or the file with its Resolver:

.. code-block:: terminal

    $ php bin/console lint:design-tokens design/foundation.tokens.json design/theme.resolver.json

A file linted without the Resolver that completes it must resolve on its own.

``--fix`` rewrites each valid document in canonical form: four-space
indentation, unescaped Unicode and slashes, and one final newline. It keeps
references, metadata and entry order, and resolves nothing. Gate it in CI like
any formatter:

.. code-block:: terminal

    $ php bin/console lint:design-tokens design/ --fix
    $ git diff --exit-code design/

``debug:design-tokens`` prints the resolved tokens, optionally under a path
prefix. ``--sources`` names the file each value comes from, and the files it
replaced:

.. code-block:: terminal

    $ php bin/console debug:design-tokens color.action --sources

.. code-block:: text

     ---------------------- ------- ------------------- ------------------- ------------------ --------------
      Path                   Type    CSS value           Source              Replaced           Description
     ---------------------- ------- ------------------- ------------------- ------------------ --------------
      color.action.primary   color   color(srgb 1 0 0)   brand.tokens.json   base.tokens.json   Brand action
     ---------------------- ------- ------------------- ------------------- ------------------ --------------

.. _`DTCG 2025.10`: https://www.designtokens.org/tr/2025.10/
