<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Dependency;

use Symfony\UX\Toolkit\Assert;

/**
 * Represents a dependency on a Stimulus controller.
 *
 * @internal
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class StimulusControllerDependency implements DependencyInterface
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        public string $name,
    ) {
        Assert::stimulusControllerName($this->name);
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
