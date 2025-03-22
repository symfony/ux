<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Threejs\Twig;

use Twig\TwigFunction;
use Symfony\UX\Threejs\Three;
use Twig\Extension\AbstractExtension;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;

/**
 * @author Sylvain Blondeau <contact@sylvainblondeau.dev>
 *
 */
final class ThreejsExtension extends AbstractExtension
{
    private $stimulus;

    /**
     * @param $stimulus StimulusHelper
     */
    public function __construct(StimulusHelper $stimulus)
    {
        $this->stimulus = $stimulus;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_threejs', [$this, 'renderThreejs'], ['is_safe' => ['html']]),
        ];
    }

    public function renderThreejs(Three $threejs): string
    {
        $controllers = [];

        $controllers['@symfony/ux-threejs/three'] = ['three' => $threejs->createThree()];

        $stimulusAttributes = $this->stimulus->createStimulusAttributes();
        foreach ($controllers as $name => $controllerValues) {
            $stimulusAttributes->addController($name, $controllerValues);
        }

        return \sprintf('<div %s></div>', $stimulusAttributes);
    }
}
