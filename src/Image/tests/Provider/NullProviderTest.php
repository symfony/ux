<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Provider;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Exception\LogicException;
use Symfony\UX\Image\ImageTransformation;
use Symfony\UX\Image\Provider\NullProvider;

final class NullProviderTest extends TestCase
{
    public function testGenerateUrlThrowsAndNamesTheInstallableBridges()
    {
        $provider = new NullProvider();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('symfony/ux-glide-image');
        $this->expectExceptionMessageMatches('/symfony\/ux-keycdn-image/');
        $this->expectExceptionMessageMatches('/symfony\/ux-cloudflare-image/');

        $provider->generateUrl(new ImageTransformation('/foo.png'));
    }
}
