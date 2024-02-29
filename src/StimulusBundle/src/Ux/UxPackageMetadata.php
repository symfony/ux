<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Ux;

/**
 * @internal
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
class UxPackageMetadata
{
    public function __construct(
        public string $packageDirectory,
        public array $symfonyConfig,
        /**
         * The package name, without the @.
         */
        public string $packageName,
    ) {
    }
}
