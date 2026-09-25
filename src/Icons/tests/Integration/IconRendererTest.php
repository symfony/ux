<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Icons\Icon;
use Symfony\UX\Icons\IconRenderer;
use Symfony\UX\Icons\IconRendererInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class IconRendererTest extends KernelTestCase
{
    public function testIconRenderService(): void
    {
        $this->assertTrue(self::getContainer()->has(IconRendererInterface::class));
    }

    public function testIconRendererAlias(): void
    {
        $renderer = self::getContainer()->get(IconRendererInterface::class);
        $this->assertInstanceOf(IconRenderer::class, $renderer);
    }

    public function testIconRendererIsPrivate(): void
    {
        $this->assertFalse(self::getContainer()->has(IconRenderer::class));
    }

    public function testIconsAreFetchedAgainAfterServicesReset(): void
    {
        $container = self::getContainer();
        $renderer = $container->get(IconRendererInterface::class);
        $cache = $container->get('cache.system_clearer')->getPool('.ux_icons.cache');

        try {
            $cache->get('reset-test', static fn () => new Icon('<path d="old"/>'), \INF);
            $this->assertStringContainsString('<path d="old"/>', $renderer->renderIcon('reset-test'));

            $cache->get('reset-test', static fn () => new Icon('<path d="new"/>'), \INF);
            $container->get('services_resetter')->reset();

            $this->assertStringContainsString('<path d="new"/>', $renderer->renderIcon('reset-test'));
        } finally {
            $cache->delete('reset-test');
        }
    }
}
