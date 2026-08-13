<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\UXImageBundle;

#[CoversClass(UXImageBundle::class)]
final class UXImageBundleTest extends TestCase
{
    public function testGetPath(): void
    {
        $bundle = new UXImageBundle();

        self::assertSame(\dirname(__DIR__), $bundle->getPath());
    }

    public function testGetContainerExtension(): void
    {
        $bundle = new UXImageBundle();

        self::assertSame('ux_image', $bundle->getContainerExtension()->getAlias());
    }
}
