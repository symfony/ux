<?php

/*
 * This file is part of the Symfony StimulusBundle package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\AssetMapper;

use Symfony\Component\AssetMapper\MappedAsset;

/**
 * @experimental
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
class MappedControllerAsset
{
    public function __construct(
        public MappedAsset $asset,
        public bool $isLazy,
    ) {
    }
}
