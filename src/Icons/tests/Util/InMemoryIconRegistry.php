<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Tests\Util;

use Symfony\UX\Icons\Exception\IconNotFoundException;
use Symfony\UX\Icons\IconRegistryInterface;
use Symfony\UX\Icons\Svg\Icon;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class InMemoryIconRegistry implements IconRegistryInterface
{
    public function __construct(
        /**
         * @var array<string, Icon> $icons
         */
        private array $icons = [],
    ) {
    }

    public function set(string $name, Icon $icon): void
    {
        $this->icons[$name] = $icon;
    }

    public function get(string $name): Icon
    {
        return $this->icons[$name] ?? throw new IconNotFoundException(sprintf('Icon "%s" not found.', $name));
    }
}
