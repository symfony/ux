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
        $publicUrl = 'http://localhost:9090/.well-known/mercure';

        yield [
            [['/topic/1', '/topic/2']],
            '<div '.
                'data-controller="symfony--ux-notify--notify" '.
                'data-symfony--ux-notify--notify-topics-value="[&quot;\/topic\/1&quot;,&quot;\/topic\/2&quot;]" '.
                'data-symfony--ux-notify--notify-hub-value="'.$publicUrl.'"'.
            '></div>',
        ];

        yield [
            ['/topic/1'],
            '<div '.
                'data-controller="symfony--ux-notify--notify" '.
                'data-symfony--ux-notify--notify-topics-value="[&quot;\/topic\/1&quot;]" '.
                'data-symfony--ux-notify--notify-hub-value="'.$publicUrl.'"'.
            '></div>',
        ];

        yield [
            [],
            '<div '.
                'data-controller="symfony--ux-notify--notify" '.
                'data-symfony--ux-notify--notify-topics-value="[&quot;https:\/\/symfony.com\/notifier&quot;]" '.
                'data-symfony--ux-notify--notify-hub-value="'.$publicUrl.'"'.
            '></div>',
        ];
    }
}
