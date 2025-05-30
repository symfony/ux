<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\File;

use Symfony\UX\Toolkit\Dependency\DependencyInterface;
use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
use Symfony\UX\Toolkit\Dependency\Version;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class ComponentMeta
{
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        unset($data['$schema']);

        $dependencies = [];
        foreach ($data['dependencies'] ?? [] as $i => $dependency) {
            if (!isset($dependency['type'])) {
                throw new \InvalidArgumentException(sprintf('The dependency type is missing for dependency #%d, add "type" key.', $i));
            }

            if ('php' === $dependency['type']) {
                $package = $dependency['package'] ?? throw new \InvalidArgumentException(sprintf('The package name is missing for dependency #%d.', $i));
                if (str_contains($package, ':')) {
                    [$name, $version] = explode(':', $package, 2);
                    $dependencies[] = new PhpPackageDependency($name, new Version($version));
                } else {
                    $dependencies[] = new PhpPackageDependency($package);
                }
            } else {
                throw new \InvalidArgumentException(\sprintf('The dependency type "%s" is not supported.', $dependency['type']));
            }
        }
        unset($data['dependencies']);

        if ([] !== $unused = array_keys($data)) {
            throw new \InvalidArgumentException(\sprintf('The following key(s) are not supported: "%s".', implode('", "', $unused)));
        }

        return new self(
            $dependencies
        );
    }

    /**
     * @param list<DependencyInterface> $dependencies
     */
    private function __construct(
        public readonly array $dependencies
    ) {
    }
}
