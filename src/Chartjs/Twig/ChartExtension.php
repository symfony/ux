<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Chartjs\Twig;

use Symfony\UX\Chartjs\Model\Chart;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @final
 * @experimental
 */
class ChartExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_chart', [$this, 'renderChart'], ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    public function renderChart(Environment $env, Chart $chart, array $attributes = []): string
    {
        $chart->setAttributes(array_merge($chart->getAttributes(), $attributes));

        $html = '
            <canvas
                data-controller="'.trim($chart->getDataController().' @symfony/ux-chartjs/chart').'"
                data-view="'.twig_escape_filter($env, json_encode($chart->createView()), 'html_attr').'"
        ';

        foreach ($chart->getAttributes() as $name => $value) {
            if ('data-controller' === $name) {
                continue;
            }

            if (true === $value) {
                $html .= $name.'="'.$name.'" ';
            } elseif (false !== $value) {
                $html .= $name.'="'.$value.'" ';
            }
        }

        return trim($html).'></canvas>';
    }
}
