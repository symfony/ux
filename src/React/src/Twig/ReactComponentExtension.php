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
use Symfony\WebpackEncoreBundle\Twig\StimulusTwigExtension;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @final
 */
class ReactComponentExtension extends AbstractExtension
{
    private $stimulusHelper;

    /**
     * @param $stimulus StimulusHelper
     */
    public function __construct(StimulusHelper|StimulusTwigExtension $stimulus)
    {
        if ($stimulus instanceof StimulusTwigExtension) {
            trigger_deprecation('symfony/ux-react', '2.9', 'Passing an instance of "%s" to "%s" is deprecated, pass an instance of "%s" instead.', StimulusTwigExtension::class, __CLASS__, StimulusHelper::class);
            $stimulus = new StimulusHelper(null);
        }

        $this->stimulusHelper = $stimulus;
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
