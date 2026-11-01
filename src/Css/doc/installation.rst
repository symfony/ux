Installation
============

Install the bundle with Composer:

.. code-block:: terminal

    $ composer require symfony/ux-css

Symfony Flex registers ``UXCssBundle`` for you. Its recipe creates
``config/packages/ux_css.yaml``, with the default tokens of Panda CSS enabled
(see :doc:`tokens`), and imports the stylesheet from ``assets/app.js``. If your
application does not use Flex, register the bundle manually::

    // config/bundles.php
    return [
        // ...
        Symfony\UX\Css\UXCssBundle::class => ['all' => true],
    ];

UX CSS installs no JavaScript and needs no Node.js: PHP writes the CSS file.

In debug mode, the bundle also writes ``config/reference_css.php``, the types
that editors read to complete ``css()`` hashes. Commit it. See
:doc:`editors`.

Requirements
------------

* PHP 8.4 or later;
* Symfony 7.4 or 8.x;
* Symfony TwigBundle 7.4 or 8.x;
* Twig 3.24 or later.

TwigBundle registers the ``css()`` function and gives the bundle the list of
templates to read.

Load the stylesheet
-------------------

The bundle writes its CSS to ``var/ux_css/styles.css``. Import it from your
JavaScript entry, after your own styles:

.. code-block:: javascript

    // assets/app.js
    import './styles/app.css';
    import '../var/ux_css/styles.css';

The Symfony Flex recipe adds this line for you. It works with AssetMapper,
Symfony Reprise and Webpack Encore. The bundle registers ``var/ux_css/`` as an
AssetMapper path under the ``ux_css`` prefix, so there is nothing to configure.
:doc:`bundlers` gives the details for each tool.

Next, :doc:`getting-started` declares your first tokens.
