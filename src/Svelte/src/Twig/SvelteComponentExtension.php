<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Svelte\Twig;

use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use Symfony\WebpackEncoreBundle\Twig\StimulusTwigExtension;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @author Thomas Choquet <thomas.choquet.pro@gmail.com>
 *
 * @final
 */
class SvelteComponentExtension extends AbstractExtension
{
    private $stimulusHelper;

    /**
     * @param $stimulus StimulusHelper
     */
    public function __construct(StimulusHelper|StimulusTwigExtension $stimulus)
    {
        if ($stimulus instanceof StimulusTwigExtension) {
            trigger_deprecation('symfony/ux-svelte', '2.9', 'Passing an instance of "%s" to "%s" is deprecated, pass an instance of "%s" instead.', StimulusTwigExtension::class, __CLASS__, StimulusHelper::class);
            $stimulus = new StimulusHelper(null);
        }

        $this->stimulusHelper = $stimulus;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('svelte_component', [$this, 'renderSvelteComponent'], ['is_safe' => ['html_attr']]),
        ];
    }

    public function renderSvelteComponent(string $componentName, array $props = [], bool $intro = false): string
    {
        $params = ['component' => $componentName];
        if ($props) {
            $params['props'] = $props;
        }
        if ($intro) {
            $params['intro'] = true;
        }

        $stimulusAttributes = $this->stimulusHelper->createStimulusAttributes();
        $stimulusAttributes->addController('@symfony/ux-svelte/svelte', $params);

        return (string) $stimulusAttributes;
    }
}
