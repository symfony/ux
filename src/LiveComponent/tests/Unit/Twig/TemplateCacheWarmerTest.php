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
use Symfony\UX\LiveComponent\Twig\TemplateCacheWarmer;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class TemplateCacheWarmerTest extends TestCase
{
    private string $cacheDir;
    private string $cacheFile;
    private TemplateCacheWarmer $templateCacheWarmer;

    protected function setUp(): void
    {
        $this->cacheDir ??= sys_get_temp_dir();
        $this->cacheFile ??= $this->cacheDir.'/cache_file';
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
        $this->templateCacheWarmer ??= new TemplateCacheWarmer(
            new \ArrayObject(['template1', 'template2']),
            'cache_file',
            'secret'
        );
    }

    public function testWarmUpCreatesCacheFile(): void
    {
        $this->assertFileDoesNotExist($this->cacheFile);

        $this->templateCacheWarmer->warmUp($this->cacheDir);

        $this->assertFileExists($this->cacheFile);
    }

    public function testWarmUpCreatesCorrectCacheContent(): void
    {
        $this->templateCacheWarmer->warmUp($this->cacheDir);
        $adapter = new PhpArrayAdapter($this->cacheFile, new NullAdapter());
        $item = $adapter->getItem('map');

        $this->assertSame(
            [
                hash('xxh128', 'template1secret') => 'template1',
                hash('xxh128', 'template2secret') => 'template2',
            ],
            $item->get()
        );
    }

    public function testWarmUpCreatesReproductibleTemplateMap(): void
    {
        $this->templateCacheWarmer->warmUp($this->cacheDir);
        $adapter = new PhpArrayAdapter($this->cacheFile, new NullAdapter());
        $map1 = $adapter->getItem('map')->get();

        $this->templateCacheWarmer->warmUp($this->cacheDir);
        $adapter = new PhpArrayAdapter($this->cacheFile, new NullAdapter());
        $map2 = $adapter->getItem('map')->get();

        $this->assertSame($map1, $map2);
    }
}
