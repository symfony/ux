<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\File;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\File\File;
use Symfony\UX\Toolkit\File\FileType;

final class FileTest extends TestCase
{
    public function testShouldFailIfPathIsNotRelative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The path to the kit "%s" must be relative.', __FILE__.'/templates/components/Button.html.twig'));

        new File(FileType::Twig, __FILE__.'/templates/components/Button.html.twig', __FILE__.'Button.html.twig');
    }

    public function testShouldFailIfPathNameIsNotRelative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The path name "%s" must be relative.', __FILE__.'Button.html.twig'));

        new File(FileType::Twig, 'templates/components/Button.html.twig', __FILE__.'Button.html.twig');
    }

    public function testShouldFailIfPathNameIsNotASubpathOfPathToKit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The relative path name "%s" must be a subpath of the relative path to the kit "%s".', 'foo/bar/Button.html.twig', 'templates/components/Button.html.twig'));

        new File(FileType::Twig, 'templates/components/Button.html.twig', 'foo/bar/Button.html.twig');
    }

    public function testCanInstantiateFile(): void
    {
        $file = new File(FileType::Twig, 'templates/components/Button.html.twig', 'Button.html.twig');

        $this->assertSame(FileType::Twig, $file->type);
        $this->assertSame('templates/components/Button.html.twig', $file->relativePathNameToKit);
        $this->assertSame('Button.html.twig', $file->relativePathName);
        $this->assertSame('templates/components/Button.html.twig (Twig)', (string) $file);
    }

    public function testCanInstantiateFileWithSubComponent(): void
    {
        $file = new File(FileType::Twig, 'templates/components/Table/Body.html.twig', 'Table/Body.html.twig');

        $this->assertSame(FileType::Twig, $file->type);
        $this->assertSame('templates/components/Table/Body.html.twig', $file->relativePathNameToKit);
        $this->assertSame('Table/Body.html.twig', $file->relativePathName);
        $this->assertSame('templates/components/Table/Body.html.twig (Twig)', (string) $file);
    }
}
