<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Notify\Twig;

use Twig\DeprecatedCallableInfo;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class NotifyExtension extends AbstractExtension
{
    /**
     * @return iterable<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('stream_notifications', [NotifyRuntime::class, 'renderStreamNotifications'], [
                'is_safe' => ['html'],
                ...(class_exists(DeprecatedCallableInfo::class)
                    ? ['deprecation_info' => new DeprecatedCallableInfo('symfony/ux-notify', '3.1', 'ux_notify')]
                    : ['deprecated' => '3.1', 'deprecating_package' => 'symfony/ux-notify', 'alternative' => 'ux_notify']),
            ]),
            new TwigFunction('ux_notify', [NotifyRuntime::class, 'renderStreamNotifications'], ['is_safe' => ['html']]),
        ];
    }
}
