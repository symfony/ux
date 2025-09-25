<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\React\Twig;

use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @final
 */
class ReactComponentExtension extends AbstractExtension
{
    public function __construct(private StimulusHelper $stimulusHelper)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('react_component', [$this, 'renderReactComponent'], ['is_safe' => ['html_attr']]),
        ];
    }

    /**
     * @param array<string, mixed>    $props
     * @param array{permanent?: bool} $options
     */
    public function renderReactComponent(string $componentName, array $props = [], array $options = []): string
    {
        $values = ['component' => $componentName];
        if ($props) {
            $values['props'] = $props;
        }
        if ($options) {
            if (\is_bool($permanent = $options['permanent'] ?? null)) {
                $values['permanent'] = $permanent;
            }
        }

        $stimulusAttributes = $this->stimulusHelper->createStimulusAttributes();
        $stimulusAttributes->addController('@symfony/ux-react/react', $values);

        return (string) $stimulusAttributes;
    }
}
