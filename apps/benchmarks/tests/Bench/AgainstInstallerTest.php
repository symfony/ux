<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Bench;

use App\Bench\AgainstInstaller;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AgainstInstallerTest extends TestCase
{
    #[DataProvider('provideRefs')]
    public function testItMapsARefToADirectoryName(string $ref, string $expected): void
    {
        $directoryName = AgainstInstaller::directoryName($ref);

        self::assertSame($expected, $directoryName);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideRefs(): iterable
    {
        yield 'tag' => ['v3.6.0', 'v3.6.0'];
        yield 'remote branch' => ['origin/3.x', 'origin-3.x'];
        yield 'special characters' => ['feature/x+y', 'feature-x-y'];
    }

    public function testItFindsThePackagesOfAWorktreeIncludingBridges(): void
    {
        $worktree = __DIR__.'/../Fixtures/worktree-3.6';

        $packages = AgainstInstaller::packagesIn($worktree);

        self::assertSame(['symfony/stimulus-bundle', 'symfony/ux-leaflet-map', 'symfony/ux-map'], array_keys($packages));
        self::assertSame(realpath(\sprintf('%s/src/Map', $worktree)), $packages['symfony/ux-map']);
    }
}
