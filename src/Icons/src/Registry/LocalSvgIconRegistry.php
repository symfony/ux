<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Registry;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Icons\Exception\IconNotFoundException;
use Symfony\UX\Icons\IconRegistryInterface;
use Symfony\UX\Icons\Svg\Icon;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class LocalSvgIconRegistry implements IconRegistryInterface
{
    /**
     * @param string[] $iconDirs
     */
    public function __construct(private array $iconDirs)
    {
    }

    public function get(string $name): Icon
    {
        foreach ($this->iconDirs as $dir) {
            if (file_exists($filename = sprintf('%s/%s.svg', $dir, str_replace(':', '/', $name)))) {
                return Icon::fromFile($filename);
            }
        }

        throw new IconNotFoundException(sprintf('The svg file for icon "%s" does not exist.', $name));
    }

    public function add(string $name, string $svg): void
    {
        $dir = $this->iconDirs[0] ?? throw new \LogicException('No local icon directory configured.');
        $filename = sprintf('%s/%s.svg', $dir, $name);

        (new Filesystem())->dumpFile($filename, $svg);
    }
}
