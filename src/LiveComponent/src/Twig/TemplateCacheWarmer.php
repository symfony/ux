<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Twig;

use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * @author Bart Vanderstukken <bart.vanderstukken@gmail.com>
 *
 * @internal
 */
final class TemplateCacheWarmer implements CacheWarmerInterface
{
    public function __construct(private \IteratorAggregate $templateIterator, private readonly string $cacheFilename)
    {
    }

    public function warmUp(string $cacheDir, string $buildDir = null): array
    {
        $map = [];
        foreach ($this->templateIterator as $item) {
            $map[bin2hex(random_bytes(16))] = $item;
        }

        (new PhpArrayAdapter($cacheDir.'/'.$this->cacheFilename, new NullAdapter()))->warmUp(['map' => $map]);

        return [];
    }

    public function isOptional(): bool
    {
        return false;
    }
}
