<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Svg;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\Image\Svg\RejectSvgPolicy;

#[CoversClass(RejectSvgPolicy::class)]
final class RejectSvgPolicyTest extends TestCase
{
    public function testRejectsSvgByDefault(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'ux-image-svg-');
        file_put_contents($path, '<svg xmlns="http://www.w3.org/2000/svg"/>');

        try {
            new RejectSvgPolicy()->process(new UploadedFile($path, 'image.svg', 'image/svg+xml', null, true));
            self::fail('SVG must be rejected by default.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('SVG is rejected by default', $exception->getMessage());
        } finally {
            @unlink($path);
        }
    }
}
