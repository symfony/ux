Symfony UX Chart.js
===================

Symfony UX Chart.js is a Symfony bundle integrating the
`Chart.js`_ library in Symfony applications.
It is part of `the Symfony UX initiative`_.

Installation
------------

Install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-chartjs

If you're using WebpackEncore, install your assets and restart Encore (not
needed if you're using AssetMapper):

.. code-block:: terminal

    $ npm install --force
    $ npm run watch

Usage
-----

To use Symfony UX Chart.js, inject the ``ChartBuilderInterface`` service
and create charts in PHP::

    // ...
    use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
    use Symfony\UX\Chartjs\Model\Chart;

    class HomeController extends AbstractController
    {
        #[Route('/', name: 'app_homepage')]
        public function index(ChartBuilderInterface $chartBuilder): Response
        {
            $chart = $chartBuilder->createChart(Chart::TYPE_LINE);

            $chart->setData([
                'labels' => ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
                'datasets' => [
                    [
                        'label' => 'My First dataset',
                        'backgroundColor' => 'rgb(255, 99, 132)',
                        'borderColor' => 'rgb(255, 99, 132)',
                        'data' => [0, 10, 5, 2, 20, 30, 45],
                    ],
                ],
            ]);

            $chart->setOptions([
                'scales' => [
                    'y' => [
                        'suggestedMin' => 0,
                        'suggestedMax' => 100,
                    ],
                ],
            ]);

            return $this->render('home/index.html.twig', [
                'chart' => $chart,
            ]);
        }
    }

All options and data are provided as-is to Chart.js. You can read
`Chart.js documentation`_ to discover them all.

Once created in PHP, a chart can be displayed using Twig:

.. code-block:: html+twig

    {{ render_chart(chart) }}

    {# You can pass HTML attributes as a second argument to add them on the <canvas> tag #}
    {{ render_chart(chart, {'class': 'my-chart'}) }}

Using Plugins
~~~~~~~~~~~~~

Chart.js comes with `a lot of plugins`_ to extend its default behavior. Let's see
what it looks like to use the `zoom plugin`_ by following the
`zoom plugin documentation`_.

First, install the plugin:

.. code-block:: terminal

    $ npm install chartjs-plugin-zoom -D

Then register the plugin globally. This can be done in your ``app.js`` file:

.. code-block:: javascript

    // assets/app.js
    import zoomPlugin from 'chartjs-plugin-zoom';

    // register globally for all charts
    document.addEventListener('chartjs:init', function (event) {
        const Chart = event.detail.Chart;
        Chart.register(zoomPlugin);
    });

    // ...

Finally, configure the plugin with the chart options. For example,
the zoom plugin docs show the following example config:

.. code-block:: javascript

    // ...
    options: {
        plugins: {
            zoom: {
                zoom: {
                  wheel: { enabled: true },
                  pinch: { enabled: true },
                  mode: 'xy',
                }
            }
        }
    }
    // ...

To use this same config in Symfony UX Chart.js, you can use the
``setOptions()`` method::

    $chart = $chartBuilder->createChart(Chart::TYPE_LINE);

    // ...

    $chart->setOptions([
        'plugins' => [
            'zoom' => [
                'zoom' => [
                    'wheel' => ['enabled' => true],
                    'pinch' => ['enabled' => true],
                    'mode' => 'xy',
                ],
            ],
        ],
    ]);

Extend the default behavior
~~~~~~~~~~~~~~~~~~~~~~~~~~~

Symfony UX Chart.js allows you to extend its default behavior using a
custom Stimulus controller:

.. code-block:: javascript

    // mychart_controller.js

    import { Controller } from '@hotwired/stimulus';

    export default class extends Controller {
        connect() {
            this.element.addEventListener('chartjs:pre-connect', this._onPreConnect);
            this.element.addEventListener('chartjs:connect', this._onConnect);
        }

        disconnect() {
            // You should always remove listeners when the controller is disconnected to avoid side effects
            this.element.removeEventListener('chartjs:pre-connect', this._onPreConnect);
            this.element.removeEventListener('chartjs:connect', this._onConnect);
        }

        _onPreConnect(event) {
            // The chart is not yet created
            // You can access the config that will be passed to "new Chart()"
            console.log(event.detail.config);

            // For instance you can format Y axis
            // To avoid overriding existing config, you should distinguish 3 cases:
            // # 1. No existing scales config => add a new scales config
            event.detail.config.options.scales = {
                y: {
                    ticks: {
                        callback: function (value, index, values) {
                            /* ... */
                        },
                    },
                },
            };
            // # 2. Existing scales config without Y axis config => add new Y axis config
            event.detail.config.options.scales.y = {
                ticks: {
                    callback: function (value, index, values) {
                        /* ... */
                    },
                },
            };
            // # 3. Existing Y axis config => update it
            event.detail.config.options.scales.y.ticks = {
                callback: function (value, index, values) {
                    /* ... */
                },
            };
        }

        _onConnect(event) {
            // The chart was just created
            console.log(event.detail.chart); // You can access the chart instance using the event details

            // For instance you can listen to additional events
            event.detail.chart.options.onHover = (mouseEvent) => {
                /* ... */
            };
            event.detail.chart.options.onClick = (mouseEvent) => {
                /* ... */
            };
        }
    }

Then in your render call, add your controller as an HTML attribute:

.. code-block:: twig

    {{ render_chart(chart, {'data-controller': 'mychart'}) }}

There is also a ``chartjs:init`` event that is called just *one* time before your
first chart is rendered. That's an ideal place to `register Chart.js plugins globally`_
or make other changes to any "static"/global part of Chart.js. For example,
to add a global `Tooltip positioner`_:

.. code-block:: javascript

    // assets/app.js

    // register globally for all charts
    document.addEventListener('chartjs:init', function (event) {
        const Chart = event.detail.Chart;
        const Tooltip = Chart.registry.plugins.get('tooltip');
        Tooltip.positioners.bottom = function(items) {
            /* ... */
        };
    });

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework: https://symfony.com/doc/current/contributing/code/bc.html.

.. _`Chart.js`: https://www.chartjs.org
.. _`the Symfony UX initiative`: https://ux.symfony.com/
.. _`Chart.js documentation`: https://www.chartjs.org/docs/latest/
.. _`a lot of plugins`: https://github.com/chartjs/awesome#plugins
.. _`zoom plugin`: https://www.chartjs.org/chartjs-plugin-zoom/latest/
.. _`zoom plugin documentation`: https://www.chartjs.org/chartjs-plugin-zoom/latest/guide/integration.html
.. _`register Chart.js plugins globally`: https://www.chartjs.org/docs/latest/developers/plugins.html
.. _`Tooltip positioner`: https://www.chartjs.org/docs/latest/samples/tooltip/position.html
