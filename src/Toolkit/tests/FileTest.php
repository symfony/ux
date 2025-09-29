<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\File;

final class FileTest extends TestCase
{
    public function testShouldFailIfSourcePathIsNotRelative()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The source path "%s" must be relative.', __FILE__.'/templates/components/Button.html.twig'));

        new File(__FILE__.'/templates/components/Button.html.twig', __FILE__.'Button.html.twig');
    }

    public function testShouldFailIfDestinationPathIsNotRelative()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The destination path "%s" must be relative.', __FILE__.'Button.html.twig'));

        new File('templates/components/Button.html.twig', __FILE__.'Button.html.twig');
    }

    public function testCanInstantiateFile()
    {
        $file = new File('src-templates/components/Button.html.twig', 'dist-templates/components/Button.html.twig');

        $this->assertSame('src-templates/components/Button.html.twig', $file->sourceRelativePathName);
        $this->assertSame('dist-templates/components/Button.html.twig', $file->destinationRelativePathName);
        $this->assertSame('src-templates/components/Button.html.twig', (string) $file);
    }

    public function testCanInstantiateFileWithSubComponent()
    {
        $file = new File('src-templates/components/Table/Body.html.twig', 'dest-templates/components/Table/Body.html.twig');

        $this->assertSame('src-templates/components/Table/Body.html.twig', $file->sourceRelativePathName);
        $this->assertSame('dest-templates/components/Table/Body.html.twig', $file->destinationRelativePathName);
        $this->assertSame('src-templates/components/Table/Body.html.twig', (string) $file);
    }
}
