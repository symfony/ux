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
     * @var array<string, string>|null Map of <template name> => <obscured name>
     */
    private ?array $reverseMap = null;

    public function __construct(string $cacheFile)
    {
        $this->map = PhpArrayAdapter::create($cacheFile, new NullAdapter())->getItem('map')->get();
    }

    public function resolve(string $obscuredName): string
    {
        return $this->map[$obscuredName] ?? throw new \RuntimeException(\sprintf('Cannot find a template matching "%s". Cache may be corrupt.', $obscuredName));
    }

    public function obscuredName(string $templateName): string
    {
        // This runs for every embedded child component, and scanning the whole map
        // each time made it cost more the more templates an application has.
        return ($this->reverseMap ??= array_flip($this->map))[$templateName]
            ?? throw new \RuntimeException(\sprintf('Cannot find a match for template "%s". Cache may be corrupt.', $templateName));
    }
}
