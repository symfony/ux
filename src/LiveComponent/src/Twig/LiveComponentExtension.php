<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Twig;

use Twig\DeprecatedCallableInfo;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class LiveComponentExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('component_url', [LiveComponentRuntime::class, 'getComponentUrl'], [
                ...(class_exists(DeprecatedCallableInfo::class)
                    ? ['deprecation_info' => new DeprecatedCallableInfo('symfony/ux-live-component', '3.1', 'ux_live_component_url')]
                    : ['deprecated' => '3.1', 'deprecating_package' => 'symfony/ux-live-component', 'alternative' => 'ux_live_component_url']),
            ]),
            new TwigFunction('ux_live_component_url', [LiveComponentRuntime::class, 'getComponentUrl']),
            new TwigFunction('live_action', [LiveComponentRuntime::class, 'liveAction'], [
                'is_safe' => ['html_attr'],
                ...(class_exists(DeprecatedCallableInfo::class)
                    ? ['deprecation_info' => new DeprecatedCallableInfo('symfony/ux-live-component', '3.1', 'ux_live_action')]
                    : ['deprecated' => '3.1', 'deprecating_package' => 'symfony/ux-live-component', 'alternative' => 'ux_live_action']),
            ]),
            new TwigFunction('ux_live_action', [LiveComponentRuntime::class, 'liveAction'], ['is_safe' => ['html_attr']]),
        ];
    }
}
