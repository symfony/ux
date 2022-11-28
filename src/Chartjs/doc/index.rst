Symfony UX Chart.js
===================

Symfony UX Chart.js is a Symfony bundle integrating the
`Chart.js`_ library in Symfony applications.
It is part of `the Symfony UX initiative`_.

Installation
------------

Before you start, make sure you have `Symfony UX configured in your app`_.

Then, install this bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-chartjs

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

Once created in PHP, a chart can be displayed using Twig if installed
(requires `Symfony Webpack Encore`_):

.. code-block:: twig

    {{ render_chart(chart) }}

    {# You can pass HTML attributes as a second argument to add them on the <canvas> tag #}
    {{ render_chart(chart, {'class': 'my-chart'}) }}

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
            console.log(event.detail.options); // You can access the chart options using the event details

            // For instance you can format Y axis
            event.detail.options.scales = {
                yAxes: [
                    {
                        ticks: {
                            callback: function (value, index, values) {
                                /* ... */
                            },
                        },
                    },
                ],
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

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework: https://symfony.com/doc/current/contributing/code/bc.html.

.. _`Chart.js`: https://www.chartjs.org
.. _`the Symfony UX initiative`: https://symfony.com/ux
.. _`@symfony/stimulus-bridge`: https://github.com/symfony/stimulus-bridge
.. _`Chart.js documentation`: https://www.chartjs.org/docs/latest/
.. _`Symfony Webpack Encore`: https://symfony.com/doc/current/frontend/encore/installation.html
.. _`Symfony UX configured in your app`: https://symfony.com/doc/current/frontend/ux.html
