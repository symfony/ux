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

/**
 * @author Bart Vanderstukken <bart.vanderstukken@gmail.com>
 *
 * @internal
 */
final class TemplateMap
{
    /**
     * @var array<string, string> Map of <obscured name> => <template name>
     */
    private readonly array $map;

    /**
     * @var array<string, string> Map of <template name> => <obscured name>
     */
    private readonly array $obscuredNames;

    public function __construct(string $cacheFile)
    {
        $cache = PhpArrayAdapter::create($cacheFile, new NullAdapter());

        $this->map = $cache->getItem('map')->get();
        // a cache file warmed up by an older version only holds the map
        $this->obscuredNames = $cache->getItem('obscured_names')->get() ?? array_flip($this->map);
    }

    public function resolve(string $obscuredName): string
    {
        return $this->map[$obscuredName] ?? throw new \RuntimeException(\sprintf('Cannot find a template matching "%s". Cache may be corrupt.', $obscuredName));
    }

    public function obscuredName(string $templateName): string
    {
        return $this->obscuredNames[$templateName] ?? throw new \RuntimeException(\sprintf('Cannot find a match for template "%s". Cache may be corrupt.', $templateName));
    }
}
