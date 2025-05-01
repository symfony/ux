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
use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
use Symfony\UX\Toolkit\Dependency\Version;
use Symfony\UX\Toolkit\File\File;
use Symfony\UX\Toolkit\File\FileType;
use Symfony\UX\Toolkit\Installer\Pool;

final class PoolTest extends TestCase
{
    public function testCanAddFiles(): void
    {
        $pool = new Pool();

        $this->assertCount(0, $pool->getFiles());

        $pool->addFile(new File(FileType::Twig, 'path/to/file.html.twig', 'file.html.twig'));
        $pool->addFile(new File(FileType::Twig, 'path/to/another-file.html.twig', 'another-file.html.twig'));

        $this->assertCount(2, $pool->getFiles());
    }

    public function testCantAddSameFileTwice(): void
    {
        $pool = new Pool();

        $pool->addFile(new File(FileType::Twig, 'path/to/file.html.twig', 'file.html.twig'));
        $pool->addFile(new File(FileType::Twig, 'path/to/file.html.twig', 'file.html.twig'));

        $this->assertCount(1, $pool->getFiles());
    }

    public function testCanAddPhpPackageDependencies(): void
    {
        $pool = new Pool();

        $pool->addPhpPackageDependency(new PhpPackageDependency('twig/html-extra'));

        $this->assertCount(1, $pool->getPhpPackageDependencies());
    }

    public function testCantAddSamePhpPackageDependencyTwice(): void
    {
        $pool = new Pool();

        $pool->addPhpPackageDependency(new PhpPackageDependency('twig/html-extra'));
        $pool->addPhpPackageDependency(new PhpPackageDependency('twig/html-extra'));

        $this->assertCount(1, $pool->getPhpPackageDependencies());
    }

    public function testCanAddPhpPackageDependencyWithHigherVersion(): void
    {
        $pool = new Pool();

        $pool->addPhpPackageDependency(new PhpPackageDependency('twig/html-extra', new Version(3, 11, 0)));

        $this->assertCount(1, $pool->getPhpPackageDependencies());
        $this->assertEquals('twig/html-extra:^3.11.0', (string) $pool->getPhpPackageDependencies()['twig/html-extra']);

        $pool->addPhpPackageDependency(new PhpPackageDependency('twig/html-extra', new Version(3, 12, 0)));

        $this->assertCount(1, $pool->getPhpPackageDependencies());
        $this->assertEquals('twig/html-extra:^3.12.0', (string) $pool->getPhpPackageDependencies()['twig/html-extra']);
    }
}
