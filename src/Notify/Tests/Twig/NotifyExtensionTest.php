<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Symfony\UX\Notify;

use PHPUnit\Framework\TestCase;
use Tests\Symfony\UX\Notify\Kernel\TwigAppKernel;
use Twig\Environment;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
class NotifyExtensionTest extends TestCase
{
    /**
     * @dataProvider streamNotificationsDataProvider
     */
    public function testStreamNotifications(array $params, string $expected)
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        $rendered = $container->get('test.twig.extension.notify')->streamNotifications($container->get(Environment::class), ...$params);
        $this->assertSame($expected, $rendered);
    }

    public function streamNotificationsDataProvider(): iterable
    {
        yield [
            [['/topic/1', '/topic/2'], 'https://foo.bar'],
            '<div
                style="display: none"
                data-controller="symfony--ux-notify--notify"
                data-symfony--ux-notify--notify-topics-value="[&quot;\/topic\/1&quot;,&quot;\/topic\/2&quot;]"
                data-symfony--ux-notify--notify-hub-value="https://foo.bar"
            ></div>',
        ];

        yield [
            ['/topic/1'],
            '<div
                style="display: none"
                data-controller="symfony--ux-notify--notify"
                data-symfony--ux-notify--notify-topics-value="[&quot;\/topic\/1&quot;]"
                data-symfony--ux-notify--notify-hub-value="http://localhost:9090/.well-known/mercure"
            ></div>',
        ];

        yield [
            [],
            '<div
                style="display: none"
                data-controller="symfony--ux-notify--notify"
                data-symfony--ux-notify--notify-topics-value="[&quot;https:\/\/symfony.com\/notifier&quot;]"
                data-symfony--ux-notify--notify-hub-value="http://localhost:9090/.well-known/mercure"
            ></div>',
        ];
    }
}
