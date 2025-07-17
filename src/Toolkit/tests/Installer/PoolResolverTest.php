<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Installer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\File\File;
use Symfony\UX\Toolkit\Installer\PoolResolver;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\KitSynchronizer;

final class PoolResolverTest extends TestCase
{
    public function testCanResolveDependencies(): void
    {
        $kitSynchronizer = new KitSynchronizer(new Filesystem());
        $kit = new Kit(Path::join(__DIR__, '../../kits/shadcn'), 'shadcn');
        $kitSynchronizer->synchronize($kit);

        $poolResolver = new PoolResolver();

        $pool = $poolResolver->resolveForComponent($kit, $kit->getComponent('Button'));

        $this->assertCount(1, $pool->getFiles());
        $this->assertArrayHasKey('Button.html.twig', $pool->getFiles());
        $this->assertCount(3, $pool->getPhpPackageDependencies());

        $pool = $poolResolver->resolveForComponent($kit, $kit->getComponent('Table'));

        $this->assertCount(8, $pool->getFiles());
        $this->assertArrayHasKey('Table.html.twig', $pool->getFiles());
        $this->assertArrayHasKey('Table/Row.html.twig', $pool->getFiles());
        $this->assertArrayHasKey('Table/Cell.html.twig', $pool->getFiles());
        $this->assertInstanceOf(File::class, $pool->getFiles()['Table/Head.html.twig']);
        $this->assertArrayHasKey('Table/Header.html.twig', $pool->getFiles());
        $this->assertArrayHasKey('Table/Footer.html.twig', $pool->getFiles());
        $this->assertArrayHasKey('Table/Caption.html.twig', $pool->getFiles());
        $this->assertArrayHasKey('Table/Body.html.twig', $pool->getFiles());
        $this->assertCount(1, $pool->getPhpPackageDependencies());
    }

    public function testCanHandleCircularComponentDependencies(): void
    {
        $kitSynchronizer = new KitSynchronizer(new Filesystem());
        $kit = new Kit(Path::join(__DIR__, '../Fixtures/kits/with-circular-components-dependencies'), 'with-circular-components-dependencies');
        $kitSynchronizer->synchronize($kit);

        $poolResolver = new PoolResolver();

        $pool = $poolResolver->resolveForComponent($kit, $kit->getComponent('A'));

        $this->assertCount(3, $pool->getFiles());
        $this->assertArrayHasKey('A.html.twig', $pool->getFiles());
        $this->assertArrayHasKey('B.html.twig', $pool->getFiles());
        $this->assertArrayHasKey('C.html.twig', $pool->getFiles());
        $this->assertCount(0, $pool->getPhpPackageDependencies());

        $pool = $poolResolver->resolveForComponent($kit, $kit->getComponent('B'));

        $this->assertCount(3, $pool->getFiles());
        $this->assertArrayHasKey('A.html.twig', $pool->getFiles());
        $this->assertArrayHasKey('B.html.twig', $pool->getFiles());
        $this->assertArrayHasKey('C.html.twig', $pool->getFiles());
        $this->assertCount(0, $pool->getPhpPackageDependencies());
    }
}
