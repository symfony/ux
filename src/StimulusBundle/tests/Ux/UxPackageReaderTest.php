<?php

/*
 * This file is part of the Symfony StimulusBundle package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Tests\Ux;

use PHPUnit\Framework\TestCase;
use Symfony\UX\StimulusBundle\Ux\UxPackageMetadata;
use Symfony\UX\StimulusBundle\Ux\UxPackageReader;

class UxPackageReaderTest extends TestCase
{
    public function testReadPackageMetadata()
    {
        $reader = new UxPackageReader(__DIR__.'/../fixtures');

        $metadata = $reader->readPackageMetadata('@fake-vendor/ux-package1');
        $this->assertInstanceOf(UxPackageMetadata::class, $metadata);
        $this->assertSame(__DIR__.'/../fixtures/vendor/fake-vendor/ux-package1/assets', $metadata->packageDirectory);
        $this->assertSame('fake-vendor/ux-package1', $metadata->packageName);
        $symfonyConfig = $metadata->symfonyConfig;
        $this->assertSame([
            'controller_first' => [
                'main' => 'dist/package-controller-first.js',
                'fetch' => 'eager',
                'enabled' => true,
            ],
            'controller_second' => [
                'main' => 'dist/package-controller-second.js',
                'fetch' => 'lazy',
                'enabled' => true,
            ],
        ], $symfonyConfig['controllers']);

        $metadata2 = $reader->readPackageMetadata('@fake-vendor/ux-package2');
        $this->assertInstanceOf(UxPackageMetadata::class, $metadata2);
    }

    public function testExceptionIsThrownIfPackageCannotBeFound()
    {
        $reader = new UxPackageReader(__DIR__.'/../fixtures');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not find package "fake-vendor/ux-package3" referred to from controllers.json.');

        $reader->readPackageMetadata('@fake-vendor/ux-package3');
    }
}
