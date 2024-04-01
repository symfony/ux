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
use Symfony\UX\Icons\Icon;
use Symfony\UX\Icons\IconRegistryInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class LocalSvgIconRegistry implements IconRegistryInterface
{
    public function __construct(private string $iconDir)
    {
    }

    public function get(string $name): Icon
    {
        if (!file_exists($filename = sprintf('%s/%s.svg', $this->iconDir, str_replace(':', '/', $name)))) {
            throw new IconNotFoundException(sprintf('The icon "%s" (%s) does not exist.', $name, $filename));
        }

        return Icon::fromFile($filename);
    }

    public function has(string $name): bool
    {
        try {
            $this->get($name);

            return true;
        } catch (IconNotFoundException) {
            return false;
        }
    }

    public function add(string $name, string $svg): void
    {
        $filename = sprintf('%s/%s.svg', $this->iconDir, $name);

        (new Filesystem())->dumpFile($filename, $svg);
    }
}
