Installation
============

Install the bundle with Composer:

.. code-block:: terminal

    $ composer require symfony/ux-css

UX CSS reads its tokens from `UX Design Tokens`_, which Composer installs
with it. Symfony Flex registers both bundles for you. If your application does
not use Flex, register them manually::

    // config/bundles.php
    return [
        // ...
        Symfony\UX\DesignTokens\UXDesignTokensBundle::class => ['all' => true],
        Symfony\UX\Css\UXCssBundle::class => ['all' => true],
    ];

Then point UX Design Tokens at your token files, as shown in
:doc:`getting-started`.

UX CSS installs no JavaScript and needs no Node.js: PHP writes the CSS file.

In debug mode, the bundle also writes ``config/reference_css.php``, the types
that editors read to complete ``css()`` hashes. Commit it. See
:doc:`editors`.

Requirements
------------

* PHP 8.4 or later;
* Symfony 7.4 or 8.x;
* Symfony TwigBundle 7.4 or 8.x;
* Symfony UX Design Tokens;
* Twig 3.24 or later.

TwigBundle registers the ``css()`` function and gives the bundle the list of
templates to read.

Load the stylesheet
-------------------

The bundle writes its CSS to ``var/ux_css/styles.css``. Its rules read the CSS
variables of UX Design Tokens, so the page must load both stylesheets. With
AssetMapper, add them to the base template:

.. code-block:: html+twig

    {# templates/base.html.twig #}
    {% block stylesheets %}
        {{ ux_token_stylesheet() }}
        <link rel="stylesheet" href="{{ asset('ux_css/styles.css') }}">
    {% endblock %}

The bundle registers ``var/ux_css/`` as an AssetMapper path under the
``ux_css`` prefix, so there is nothing to configure. With Webpack Encore or
Symfony Reprise, import the file from your JavaScript entry instead: see
:doc:`bundlers`.

Next, :doc:`getting-started` declares your first tokens.

.. _`UX Design Tokens`: https://symfony.com/bundles/ux-design-tokens/current/index.html
