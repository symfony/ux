<?php

/*
 * This file is part of the Symfony StimulusBundle package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Ux;

/**
 * @internal
 *
 * @experimental
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
class UxPackageMetadata
{
    public function __construct(
        public string $packageDirectory,
        public array $symfonyConfig,
        public string $packageName,
    ) {
    }
}
