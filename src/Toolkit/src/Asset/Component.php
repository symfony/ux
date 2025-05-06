<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Asset;

use Symfony\UX\Toolkit\Assert;
use Symfony\UX\Toolkit\Dependency\ComponentDependency;
use Symfony\UX\Toolkit\Dependency\DependencyInterface;
use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
use Symfony\UX\Toolkit\Dependency\StimulusControllerDependency;
use Symfony\UX\Toolkit\File\Doc;
use Symfony\UX\Toolkit\File\File;

/**
 * @internal
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class Component
{
    /**
     * @param non-empty-string $name
     * @param list<File>       $files
     */
    public function __construct(
        public readonly string $name,
        public readonly array $files,
        public ?Doc $doc = null,
        private array $dependencies = [],
    ) {
        Assert::componentName($name);

        if ([] === $files) {
            throw new \InvalidArgumentException(\sprintf('The component "%s" must have at least one file.', $name));
        }
    }

    public function addDependency(DependencyInterface $dependency): void
    {
        foreach ($this->dependencies as $i => $existingDependency) {
            if ($existingDependency instanceof PhpPackageDependency && $existingDependency->name === $dependency->name) {
                if ($existingDependency->isHigherThan($dependency)) {
                    return;
                }

                $this->dependencies[$i] = $dependency;

                return;
            }

            if ($existingDependency instanceof ComponentDependency && $existingDependency->name === $dependency->name) {
                return;
            }

            if ($existingDependency instanceof StimulusControllerDependency && $existingDependency->name === $dependency->name) {
                return;
            }
        }

        $this->dependencies[] = $dependency;
    }

    /**
     * @return list<DependencyInterface>
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }
}
