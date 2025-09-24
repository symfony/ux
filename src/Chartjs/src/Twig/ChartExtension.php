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
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @final
 */
class ChartExtension extends AbstractExtension
{
    public function __construct(private StimulusHelper $stimulusHelper)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_chart', [$this, 'renderChart'], ['is_safe' => ['html']]),
        ];
    }

    public function renderChart(Chart $chart, array $attributes = []): string
    {
        $chart->setAttributes(array_merge($chart->getAttributes(), $attributes));

        $controllers = [];
        if ($chart->getDataController()) {
            $controllers[$chart->getDataController()] = [];
        }
        $controllers['@symfony/ux-chartjs/chart'] = ['view' => $chart->createView()];

        $stimulusAttributes = $this->stimulusHelper->createStimulusAttributes();
        foreach ($controllers as $name => $controllerValues) {
            $stimulusAttributes->addController($name, $controllerValues);
        }

        foreach ($chart->getAttributes() as $name => $value) {
            if ('data-controller' === $name) {
                continue;
            }

            if (true === $value) {
                $stimulusAttributes->addAttribute($name, $name);
            } elseif (false !== $value) {
                $stimulusAttributes->addAttribute($name, $value);
            }
        }

        return \sprintf('<canvas %s></canvas>', $stimulusAttributes);
    }
}
