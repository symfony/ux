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
use Symfony\WebpackEncoreBundle\Twig\StimulusTwigExtension;
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

    /**
     * @param $stimulus StimulusHelper
     */
    public function __construct(StimulusHelper|StimulusTwigExtension $stimulus)
    {
        if ($stimulus instanceof StimulusTwigExtension) {
            trigger_deprecation('symfony/ux-chartjs', '2.9', 'Passing an instance of "%s" to "%s" is deprecated, pass an instance of "%s" instead.', StimulusTwigExtension::class, __CLASS__, StimulusHelper::class);
            $stimulus = new StimulusHelper(null);
        }

        $this->stimulus = $stimulus;
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

        $stimulusAttributes = $this->stimulus->createStimulusAttributes();
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
