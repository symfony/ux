<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Bundle;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Upload\UploaderInterface;

final class UXUploadBundleTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testBundleBoots(): void
    {
        self::bootKernel();

        self::assertTrue(self::getContainer()->has(UploaderInterface::class));
    }
}
