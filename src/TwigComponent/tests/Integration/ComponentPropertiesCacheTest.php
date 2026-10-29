<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\TwigComponent\Tests\Fixtures\Component\WithExposedVariables;
use Twig\Environment;

final class ComponentPropertiesCacheTest extends KernelTestCase
{
    private const KERNEL_OPTIONS = ['environment' => 'properties_cache', 'debug' => false];

    protected function setUp(): void
    {
        $kernel = self::createKernel(self::KERNEL_OPTIONS);

        new Filesystem()->remove($kernel->getCacheDir());
    }

    public function testRendersWithTheWarmedMetadata(): void
    {
        $this->warmUp();
        $this->changeCachedMetadata();

        $output = $this->renderComponent();

        $this->assertStringContainsString('Prop1: prop2 value', $output);
    }

    public function testWarmupReplacesStaleMetadata(): void
    {
        $this->warmUp();
        $this->changeCachedMetadata();
        $this->warmUp();

        $output = $this->renderComponent();

        $this->assertStringContainsString('Prop1: prop1 value', $output);
    }

    private function warmUp(): void
    {
        self::ensureKernelShutdown();
        self::bootKernel(self::KERNEL_OPTIONS);

        $cacheWarmer = self::getContainer()->get('cache_warmer');
        $cacheWarmer->enableOptionalWarmers();
        $cacheWarmer->warmUp(self::$kernel->getCacheDir());
    }

    private function changeCachedMetadata(): void
    {
        $pool = self::getContainer()->get('cache.ux.twig_component');
        $item = $pool->getItem('ux.twig_component.component_properties');

        $metadata = $item->get();
        // Differs from what reflection finds, to tell the cached metadata apart
        $metadata[WithExposedVariables::class]['properties']['prop1']['getter'] = 'getProp2';

        $pool->save($item->set($metadata));
    }

    private function renderComponent(): string
    {
        self::ensureKernelShutdown();
        self::bootKernel(self::KERNEL_OPTIONS);

        return self::getContainer()->get(Environment::class)->render('render_component.html.twig', [
            'name' => 'with_exposed_variables',
            'data' => [],
        ]);
    }
}
