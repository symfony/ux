<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Translator\Tests\Kernel\StableCacheDirAppKernel;

final class DumpOnContainerBuildTest extends TestCase
{
    private string $tmpDir;
    private string $environment;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/translator_bundle/'.uniqid('container_build_', true);
        // Each test has its own dump directory, so its kernel must not reuse a container class loaded by another test
        $this->environment = 'test'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->tmpDir);
    }

    public function testDumpsTranslationsWhenTheDumpDirectoryDoesNotExist(): void
    {
        $kernel = new StableCacheDirAppKernel($this->tmpDir, $this->environment);
        $kernel->boot();

        $this->assertFileExists($kernel->getDumpDir().'/index.js');
    }

    public function testDoesNotDumpTranslationsWhenTheDumpDirectoryExists(): void
    {
        $kernel = new StableCacheDirAppKernel($this->tmpDir, $this->environment);
        new Filesystem()->mkdir($kernel->getDumpDir());

        $kernel->boot();

        $this->assertFileDoesNotExist($kernel->getDumpDir().'/index.js');
    }
}
