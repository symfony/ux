<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Notify\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Notify\Tests\Kernel\TwigAppKernel;
use Symfony\UX\Notify\Twig\NotifyRuntime;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
class NotifyRuntimeTest extends TestCase
{
    /**
     * @dataProvider streamNotificationsDataProvider
     */
    public function testStreamNotifications(array $params, string $expected)
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        $runtime = $container->get('test.notify.twig_runtime');
        \assert($runtime instanceof NotifyRuntime);
        $rendered = $runtime->renderStreamNotifications(...$params);
        $this->assertSame($expected, $rendered);
    }

    public static function streamNotificationsDataProvider(): iterable
    {
        $publicUrl = 'http&#x3A;&#x2F;&#x2F;localhost&#x3A;9090&#x2F;.well-known&#x2F;mercure';

        yield [
            [['/topic/1', '/topic/2']],
            '<div '.
                'data-controller="symfony--ux-notify--notify" '.
                'data-symfony--ux-notify--notify-topics-value="&#x5B;&quot;&#x5C;&#x2F;topic&#x5C;&#x2F;1&quot;,&quot;&#x5C;&#x2F;topic&#x5C;&#x2F;2&quot;&#x5D;" '.
                'data-symfony--ux-notify--notify-hub-value="'.$publicUrl.'"'.
            '></div>',
        ];

        yield [
            ['/topic/1'],
            '<div '.
                'data-controller="symfony--ux-notify--notify" '.
                'data-symfony--ux-notify--notify-topics-value="&#x5B;&quot;&#x5C;&#x2F;topic&#x5C;&#x2F;1&quot;&#x5D;" '.
                'data-symfony--ux-notify--notify-hub-value="'.$publicUrl.'"'.
            '></div>',
        ];

        yield [
            [],
            '<div '.
                'data-controller="symfony--ux-notify--notify" '.
                'data-symfony--ux-notify--notify-topics-value="&#x5B;&quot;https&#x3A;&#x5C;&#x2F;&#x5C;&#x2F;symfony.com&#x5C;&#x2F;notifier&quot;&#x5D;" '.
                'data-symfony--ux-notify--notify-hub-value="'.$publicUrl.'"'.
            '></div>',
        ];
    }
}
