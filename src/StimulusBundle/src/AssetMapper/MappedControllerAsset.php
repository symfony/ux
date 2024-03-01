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

use Symfony\Component\AssetMapper\MappedAsset;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @internal
 */
class MappedControllerAsset
{
    public function __construct(
        public MappedAsset $asset,
        public bool $isLazy,
        /**
         * @var MappedControllerAutoImport[]
         */
        public array $autoImports = [],
    ) {
    }
}
