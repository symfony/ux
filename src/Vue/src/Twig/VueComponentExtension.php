<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Vue\Twig;

use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @author Thibault RICHARD <thibault.richard62@gmail.com>
 *
 * @final
 */
class VueComponentExtension extends AbstractExtension
{
    public function __construct(private StimulusHelper $stimulusHelper)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('vue_component', [$this, 'renderVueComponent'], ['is_safe' => ['html_attr']]),
        ];
    }

    public function renderVueComponent(string $componentName, array $props = []): string
    {
        $params = ['component' => $componentName];
        if ($props) {
            $params['props'] = $props;
        }

        $stimulusAttributes = $this->stimulusHelper->createStimulusAttributes();
        $stimulusAttributes->addController('@symfony/ux-vue/vue', $params);

        return (string) $stimulusAttributes;
    }
}
