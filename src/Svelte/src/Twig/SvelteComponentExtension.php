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

use Symfony\WebpackEncoreBundle\Twig\StimulusTwigExtension;
use Twig\Environment;
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
    private $stimulusExtension;

    public function __construct(StimulusTwigExtension $stimulusExtension)
    {
        $this->stimulusExtension = $stimulusExtension;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('svelte_component', [$this, 'renderSvelteComponent'], ['needs_environment' => true, 'is_safe' => ['html_attr']]),
        ];
    }

    public function renderSvelteComponent(Environment $env, string $componentName, array $props = [], bool $intro = false): string
    {
        $params = ['component' => $componentName];
        if ($props) {
            $params['props'] = $props;
        }
        if ($intro) {
            $params['intro'] = true;
        }

        return $this->stimulusExtension->renderStimulusController($env, '@symfony/ux-svelte/svelte', $params);
    }
}
