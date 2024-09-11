Symfony UX DataTables
=====================

Symfony UX DataTables is a Symfony bundle integrating the
`DataTables`_ library in Symfony applications.
It is part of `the Symfony UX initiative`_.

Installation
------------

.. caution::

    Before you start, make sure you have `StimulusBundle configured in your app`_.

Install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-datatables

If you're using WebpackEncore, install your assets and restart Encore (not
needed if you're using AssetMapper):

.. code-block:: terminal

    $ npm install --force
    $ npm run watch

    # or use yarn
    $ yarn install --force
    $ yarn watch

Usage
-----

To use Symfony UX DataTables, inject the ``DataTableBuilderInterface`` service
and create tables in PHP::

    // ...
    use Symfony\UX\DataTables\Builder\DataTableBuilderInterface;
    use Symfony\UX\DataTables\Model\DataTable;

    class HomeController extends AbstractController
    {
        #[Route('/', name: 'app_homepage')]
        public function index(DataTableBuilderInterface $tableBuilder): Response
        {
            $table = $tableBuilder->createDataTable('usersTable');

            $table->setData([
                'columns' => ['First name', 'Last name'],
                'data' => [
                    ['John', 'Doe'],
                    ['Jane', 'Smith'],
                ],
            ]);

            $table->setOptions([
                'order' => [
                    ['idx' => 1, 'dir' => 'asc']
                ],
            ]);

            return $this->render('home/index.html.twig', [
                'table' => $table,
            ]);
        }
    }

All options and data are provided as-is to DataTables. You can read
`DataTables documentation`_ to discover them all.

Once created in PHP, a table can be displayed using Twig:

.. code-block:: html+twig

    {{ render_datatable(table) }}

    {# You can pass HTML attributes as a second argument to add them on the <table> tag #}
    {{ render_datatable(table, {'class': 'my-table'}) }}

Extend the default behavior
~~~~~~~~~~~~~~~~~~~~~~~~~~~

Symfony UX DataTables allows you to extend its default behavior using a
custom Stimulus controller:

.. code-block:: javascript

    // mytable_controller.js

    import { Controller } from '@hotwired/stimulus';

    export default class extends Controller {
        connect() {
            this.element.addEventListener('datatables:pre-connect', this._onPreConnect);
            this.element.addEventListener('datatables:connect', this._onConnect);
        }

        disconnect() {
            // You should always remove listeners when the controller is disconnected to avoid side effects
            this.element.removeEventListener('datatables:pre-connect', this._onPreConnect);
            this.element.removeEventListener('datatables:connect', this._onConnect);
        }

        _onPreConnect(event) {
            // The table is not yet created
            // You can access the config that will be passed to "new DataTable()"
            console.log(event.detail.config);

            // For instance you can define a render callback for a given column
            event.detail.config.columns[0].render = function (data, type, row, meta) {
				return '<a href="' + data + '">Download</a>';
			}
        }

        _onConnect(event) {
            // The table was just created
            console.log(event.detail.table); // You can access the table instance using the event details

            // For instance you can listen to additional events
            event.detail.table.on('init', (event) => {
                /* ... */
            };
            event.detail.table.on('draw', (event) => {
                /* ... */
            };
        }
    }

Then in your render call, add your controller as an HTML attribute:

.. code-block:: twig

    {{ render_datatable(table, {'data-controller': 'mytable'}) }}

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework: https://symfony.com/doc/current/contributing/code/bc.html.

.. _`DataTables`: https://datatables.net
.. _`the Symfony UX initiative`: https://ux.symfony.com/
.. _`DataTables documentation`: https://datatables.net/manual/
.. _StimulusBundle configured in your app: https://symfony.com/bundles/StimulusBundle/current/index.html