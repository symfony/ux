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
use Symfony\WebpackEncoreBundle\Dto\StimulusControllersDto;
use Symfony\WebpackEncoreBundle\Twig\StimulusTwigExtension;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @final
 */
class ChartExtension extends AbstractExtension
{
    private $stimulus;

    public function __construct(StimulusTwigExtension $stimulus)
    {
        $this->stimulus = $stimulus;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_chart', [$this, 'renderChart'], ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    public function renderChart(Environment $env, Chart $chart, array $attributes = []): string
    {
        $chart->setAttributes(array_merge($chart->getAttributes(), $attributes));

        $controllers = [];
        if ($chart->getDataController()) {
            $controllers[$chart->getDataController()] = [];
        }
        $controllers['@symfony/ux-chartjs/chart'] = ['view' => $chart->createView()];

        if (class_exists(StimulusControllersDto::class)) {
            $dto = new StimulusControllersDto($env);
            foreach ($controllers as $name => $controllerValues) {
                $dto->addController($name, $controllerValues);
            }

            $html = '<canvas '.$dto.' ';
        } else {
            $html = '<canvas '.$this->stimulus->renderStimulusController($env, $controllers).' ';
        }

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
