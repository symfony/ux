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

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental
 */
final class NotifyExtension extends AbstractExtension
{
    /**
     * @return iterable<TwigFunction>
     */
    public function getFunctions(): iterable
    {
        yield new TwigFunction('stream_notifications', [NotifyRuntime::class, 'renderStreamNotifications'], ['needs_environment' => true, 'is_safe' => ['html']]);
    }
}
