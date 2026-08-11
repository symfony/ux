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
use Symfony\UX\Image\ImageBundle;

final class ImageBundleTest extends TestCase
{
    public function testBundleCanBeInstantiated()
    {
        $bundle = new ImageBundle();

        $this->assertInstanceOf(ImageBundle::class, $bundle);
    }
}
