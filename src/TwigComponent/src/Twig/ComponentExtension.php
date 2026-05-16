<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Twig;

use Twig\DeprecatedCallableInfo;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class ComponentExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('component', [ComponentRuntime::class, 'render'], [
                'is_safe' => ['all'],
                ...(class_exists(DeprecatedCallableInfo::class)
                    ? ['deprecation_info' => new DeprecatedCallableInfo('symfony/ux-twig-component', '3.1', 'ux_twig_component')]
                    : ['deprecated' => '3.1', 'deprecating_package' => 'symfony/ux-twig-component', 'alternative' => 'ux_twig_component']),
            ]),
            new TwigFunction('ux_twig_component', [ComponentRuntime::class, 'render'], ['is_safe' => ['all']]),
            new TwigFunction('provide', [ComponentRuntime::class, 'provide'], [
                ...(class_exists(DeprecatedCallableInfo::class)
                    ? ['deprecation_info' => new DeprecatedCallableInfo('symfony/ux-twig-component', '3.1', 'ux_twig_provide')]
                    : ['deprecated' => '3.1', 'deprecating_package' => 'symfony/ux-twig-component', 'alternative' => 'ux_twig_provide']),
            ]),
            new TwigFunction('ux_twig_provide', [ComponentRuntime::class, 'provide']),
            new TwigFunction('inject', [ComponentRuntime::class, 'inject'], [
                ...(class_exists(DeprecatedCallableInfo::class)
                    ? ['deprecation_info' => new DeprecatedCallableInfo('symfony/ux-twig-component', '3.1', 'ux_twig_inject')]
                    : ['deprecated' => '3.1', 'deprecating_package' => 'symfony/ux-twig-component', 'alternative' => 'ux_twig_inject']),
            ]),
            new TwigFunction('ux_twig_inject', [ComponentRuntime::class, 'inject']),
        ];
    }

    public function getTokenParsers(): array
    {
        return [
            new ComponentTokenParser(),
            new PropsTokenParser(),
        ];
    }
}
