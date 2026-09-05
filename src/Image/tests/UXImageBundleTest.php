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

use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\UXImageBundle;

final class UXImageBundleTest extends TestCase
{
    public function testBundleHasTheExpectedExtensionAlias()
    {
        self::assertSame('ux_image', new UXImageBundle()->getContainerExtension()->getAlias());
    }
}
