<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\UX\Turbo\DependencyInjection\TurboExtension;

final class TurboExtensionTest extends TestCase
{
    public function testBroadcastListenerIsResetBetweenRequests(): void
    {
        $container = new ContainerBuilder();
        new TurboExtension()->load([['broadcast' => ['doctrine_orm' => ['enabled' => true]]]], $container);

        $this->assertSame(
            [['method' => 'reset']],
            $container->getDefinition('turbo.doctrine.event_listener')->getTag('kernel.reset'),
        );
    }

    public function testCookieDomainErrorsAreNotIgnoredByDefault(): void
    {
        $container = new ContainerBuilder();
        new TurboExtension()->load([], $container);

        $this->assertFalse($container->getParameter('.turbo.mercure.ignore_cookie_domain_errors'));
    }

    public function testCookieDomainErrorsCanBeIgnored(): void
    {
        $container = new ContainerBuilder();
        new TurboExtension()->load([['mercure' => ['ignore_cookie_domain_errors' => true]]], $container);

        $this->assertTrue($container->getParameter('.turbo.mercure.ignore_cookie_domain_errors'));
    }
}
