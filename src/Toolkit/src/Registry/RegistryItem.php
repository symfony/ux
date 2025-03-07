<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Registry;

use Symfony\Component\Finder\SplFileInfo;

/**
 * @internal
 */
final readonly class RegistryItem
{
    /**
     * https://regex101.com/r/8NcORd/1.
     */
    private const REGEX_RELATIVE_FILE = '#^(?P<theme>default|new-york)/(?P<type>component|example)s/(?P<nameOrParentName>[A-Z][a-zA-Z]*)(?:/(?P<name>[A-Z][a-zA-Z]*))?\.html\.twig$#';

    public function __construct(
        public string $name,
        public RegistryItemType $type,
        public string $theme,
        public ?string $parentName,
        public string $code,
        public array $dependencies = [],
    ) {
    }

    public function getChildren(): array
    {
        $dependencies = $this->dependencies;
        $dependencies = array_unique($dependencies);

        return $dependencies;
    }

    public function getParents(): array
    {
        $parents = [];
        if (null !== $this->parentName) {
            $parents[] = $this->parentName;
        }

        return $parents;
    }
}
