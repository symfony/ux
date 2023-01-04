Symfony UX BootstrapTable
===================

Symfony UX DataTable is a Symfony bundle integrating the
`Simple-DataTables`_ library in Symfony applications.
It is part of `the Symfony UX initiative`_.

Installation
------------

Before you start, make sure you have `Symfony UX configured in your app`_.

Then, install this bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-datatable

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

To use Symfony UX DataTable, inject the ``TableBuilderInterface`` service
and create a table ::

Minimalistic example::

        $table = $tableBuilder
                ->addData([
                        ['id' => 1, 'pseudo' => 'Bob'],
                        ['id' => 2, 'pseudo' => 'Kitty'],
                    ])
                    ->addOptions([
                        'paging' => false,
                        'searchable' => false,
                    ])
                ->createTable();

A more complete example from a controller::

    #[Route('/usersList', name: 'app_users_list')]
    public function index(TableBuilderInterface $tableBuilder, UserRepository $userRepository): Response
    {
        $manifestPath = $this->getParameter('kernel.project_dir').'/public/build/manifest.json';
        $package = new Package(new JsonManifestVersionStrategy($manifestPath));

        $users = $userRepository->getUsersWithPostsAndCommentsCount();
        $table = $tableBuilder
                    ->addData($users)
                    ->addOptions([
                        'paging' => true,
                        'perPage' => 10,
                        'searchable' => true,
                        // Optionally import Simple-DataTables CSS file from CDN
                        'withPluginCss' => true,
                        // Optionally add custom CSS file, here through webpack entry and manifest.json
                        'withCss' => $package->getUrl('build/datatable.css'),
                        // Customize labels (@see https://github.com/fiduswriter/Simple-DataTables/wiki/labels)
                        'labels' => [
                            'placeholder' => 'Find a customer...',
                            'perPage' => 'Show {select} customers per page',
                        ],
                        // Customize layout (@see https://github.com/fiduswriter/Simple-DataTables/wiki/layout)
                        'layout' => [
                            'top' => '{search}{select}',
                            'bottom' => '{info}{pager}',
                        ],
                        // Customize columns (@see
                        'columns' => [
                            ['select' => 3, 'sort' => 'desc'],
                        ],
                        // Useful accessibility options
                        'rowNavigation' => true,
                        'tabIndex' => 1,
                        // ... and more
                    ])
                    ->createTable();

        return $this->render('datatable/index.html.twig', [
            'table' => $table,
        ]);
    }

You can pass most of the options supported by the `Simple-DataTables`_ library.
Check out the `Simple-DataTables documentation`_  to discover them all.

Once created in PHP, a table can be displayed using Twig if installed
(requires `Symfony Webpack Encore`_):

.. code-block:: twig

    {{ render_table(table) }}

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework: https://symfony.com/doc/current/contributing/code/bc.html.

.. _`Simple-DataTables`: https://github.com/fiduswriter/Simple-DataTables
.. _`the Symfony UX initiative`: https://symfony.com/ux
.. _`@symfony/stimulus-bridge`: https://github.com/symfony/stimulus-bridge
.. _`Simple-DataTables documentation`: https://github.com/fiduswriter/Simple-DataTables/wiki/
.. _`Symfony Webpack Encore`: https://symfony.com/doc/current/frontend/encore/installation.html
.. _`Symfony UX configured in your app`: https://symfony.com/doc/current/frontend/ux.html
