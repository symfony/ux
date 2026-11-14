<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\LiveComponent\Twig\TemplateMap;

final class TemplateMapTest extends TestCase
{
    private string $cacheFile;

    protected function setUp(): void
    {
        $this->cacheFile = sys_get_temp_dir().'/ux-live-component-template-map-'.bin2hex(random_bytes(6)).'.php';

        PhpArrayAdapter::create($this->cacheFile, new NullAdapter())->warmUp(['map' => [
            'b3f1a7' => 'components/Alert.html.twig',
            '9c2e45' => 'components/Modal.html.twig',
        ]]);
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->cacheFile);
    }

    public function testResolvesATemplateName(): void
    {
        $map = new TemplateMap($this->cacheFile);

        $this->assertSame('components/Modal.html.twig', $map->resolve('9c2e45'));
    }

    public function testThrowsOnAnUnknownObscuredName(): void
    {
        $map = new TemplateMap($this->cacheFile);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot find a template matching "nope". Cache may be corrupt.');

        $map->resolve('nope');
    }

    public function testFindsTheObscuredNameOfATemplate(): void
    {
        $map = new TemplateMap($this->cacheFile);

        $this->assertSame('b3f1a7', $map->obscuredName('components/Alert.html.twig'));
        $this->assertSame('b3f1a7', $map->obscuredName('components/Alert.html.twig'));
        $this->assertSame('9c2e45', $map->obscuredName('components/Modal.html.twig'));
    }

    public function testThrowsOnAnUnknownTemplateName(): void
    {
        $map = new TemplateMap($this->cacheFile);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot find a match for template "components/Unknown.html.twig". Cache may be corrupt.');

        $map->obscuredName('components/Unknown.html.twig');
    }
}
