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

namespace Symfony\UX\Toolkit\Installer;

use Symfony\UX\Toolkit\Asset\Component;
use Symfony\UX\Toolkit\Dependency\ComponentDependency;
use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
use Symfony\UX\Toolkit\Dependency\StimulusControllerDependency;
use Symfony\UX\Toolkit\Kit\Kit;

final class PoolResolver
{
    public function resolveForComponent(Kit $kit, Component $component): Pool
    {
        $pool = new Pool();

        // Process the component and its dependencies
        $componentsStack = [$component];
        $visitedComponents = new \SplObjectStorage();

        while (!empty($componentsStack)) {
            $currentComponent = array_pop($componentsStack);

            // Skip circular references
            if ($visitedComponents->contains($currentComponent)) {
                continue;
            }

            $visitedComponents->attach($currentComponent);

            foreach ($currentComponent->files as $file) {
                $pool->addFile($file);
            }

            foreach ($currentComponent->getDependencies() as $dependency) {
                if ($dependency instanceof ComponentDependency) {
                    $componentsStack[] = $kit->getComponent($dependency->name);
                } elseif ($dependency instanceof PhpPackageDependency) {
                    $pool->addPhpPackageDependency($dependency);
                } elseif ($dependency instanceof StimulusControllerDependency) {
                    if (null === $stimulusController = $kit->getStimulusController($dependency->name)) {
                        throw new \RuntimeException(\sprintf('Stimulus controller "%s" not found.', $dependency->name));
                    }

                    foreach ($stimulusController->files as $file) {
                        $pool->addFile($file);
                    }
                } else {
                    throw new \RuntimeException(\sprintf('Unknown dependency type: %s', $dependency::class));
                }
            }
        }

        return $pool;
    }
}
