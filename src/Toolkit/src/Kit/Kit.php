<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Kit;

use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Assert;
use Symfony\UX\Toolkit\Component\Component;

/**
 * @internal
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class Kit
{
    /**
     * @param non-empty-string                          $path
     * @param non-empty-string                          $name
     * @param non-empty-string                          $homepage
     * @param list<array{name: string, email?: string}> $authors
     * @param non-empty-string                          $license
     * @param list<Component>                           $components
     */
    public function __construct(
        public readonly string $path,
        public readonly string $name,
        public readonly string $homepage,
        public readonly array $authors,
        public readonly string $license,
        private array $components = [],
    ) {
        Assert::kitName($this->name);

        if (!Path::isAbsolute($this->path)) {
            throw new \InvalidArgumentException(\sprintf('Kit path "%s" is not absolute.', $this->path));
        }

        if (!filter_var($this->homepage, \FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException(\sprintf('Invalid homepage URL "%s".', $this->homepage));
        }
    }

    /**
     * @throws \InvalidArgumentException if the component is already registered in the kit
     */
    public function addComponent(Component $component): void
    {
        foreach ($this->components as $existingComponent) {
            if ($existingComponent->name === $component->name) {
                throw new \InvalidArgumentException(\sprintf('Component "%s" is already registered in the kit.', $component->name));
            }
        }

        $this->components[] = $component;
    }

    public function getComponents(): array
    {
        return $this->components;
    }

    public function getComponent(string $name): ?Component
    {
        foreach ($this->components as $component) {
            if ($component->name === $name) {
                return $component;
            }
        }

        return null;
    }
}
