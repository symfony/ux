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
    /**
     * @param array<string, string> $iconSetPaths
     */
    public function __construct(
        private readonly string $iconDir,
        private readonly array $iconSetPaths = [],
    ) {
    }

    public function get(string $name): Icon
    {
        if (str_contains($name, ':')) {
            [$prefix, $icon] = explode(':', $name, 2) + ['', ''];
            if ('' === $prefix || '' === $icon) {
                throw new IconNotFoundException(\sprintf('The icon name "%s" is not valid.', $name));
            }

            if ($prefixPath = $this->iconSetPaths[$prefix] ?? null) {
                if (!file_exists($filename = $prefixPath.'/'.str_replace(':', '/', $icon).'.svg')) {
                    throw new IconNotFoundException(\sprintf('The icon "%s" (%s) does not exist.', $name, $filename));
                }

                return Icon::fromFile($filename);
            }
        }

        $filepath = str_replace(':', '/', $name).'.svg';
        if (file_exists($filename = $this->iconDir.'/'.$filepath)) {
            return Icon::fromFile($filename);
        }

        throw new IconNotFoundException(\sprintf('The icon "%s" (%s) does not exist.', $name, $filename));
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
        $filename = \sprintf('%s/%s.svg', $this->iconDir, $name);

        (new Filesystem())->dumpFile($filename, $svg);
    }
}
