<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\AssetMapper;

/**
 * @internal
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
class MappedControllerAutoImport
{
    public function __construct(
        public string $path,
        public bool $isBareImport,
    ) {
    }
}
