Symfony UX BootstrapTable
===================

Symfony UX Bootstrap Table is a Symfony bundle integrating the
`Bootstrap Table`_ library in Symfony applications.
It is part of `the Symfony UX initiative`_.

Installation
------------

Before you start, make sure you have `Symfony UX configured in your app`_.

Then, install this bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/bootstrap-table

    # Don't forget to install the JavaScript dependencies as well and compile
    $ npm install --force
    $ npm run watch

    # or use yarn
    $ yarn install --force
    $ yarn watch

Also make sure you have at least version 3.0 of `@symfony/stimulus-bridge`_
in your ``package.json`` file.

Usage
-----

To use Symfony UX Bootstrap Table, inject the ``TableBuilderInterface`` service
and create table in PHP::

    // ...
    use Symfony\UX\BootstrapTable\Builder\TableBuilderInterface;

    class HomeController extends AbstractController
    {
        #[Route("/", name: "app_index")]
        public function index(TableBuilderInterface $tableBuilder): Response
        {
            $table = $tableBuilder
                ->addColumns(['id', 'pseudo'])
                ->addData([
                    ['id' => 1, 'pseudo' => 'Bob'],
                    ['id' => 2, 'pseudo' => 'Kitty']
                ])
                ->addColumnsAttributes([
                    'id' => ['data-align' => 'right']
                ])
                ->addTableAttributes([
                    'data-search' => 'true'
                ])
                ->createTable()
            ;

            return $this->render('index.html.twig', [
                'table' => $table
            ]);
        }
    }

All options and data are provided as-is to Bootstrap Table. You can read
`BootstrapTable documentation`_ to discover them all.

Once created in PHP, a chart can be displayed using Twig if installed
(requires `Symfony Webpack Encore`_):

.. code-block:: twig

    {{ render_table(table) }}

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework: https://symfony.com/doc/current/contributing/code/bc.html.

.. _`Bootstrap Table`: https://bootstrap-table.com/
.. _`the Symfony UX initiative`: https://symfony.com/ux
.. _`@symfony/stimulus-bridge`: https://github.com/symfony/stimulus-bridge
.. _`BootstrapTable documentation`: https://bootstrap-table.com/docs/getting-started/introduction/
.. _`Symfony Webpack Encore`: https://symfony.com/doc/current/frontend/encore/installation.html
.. _`Symfony UX configured in your app`: https://symfony.com/doc/current/frontend/ux.html
