<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Metadata;

use Symfony\UX\TwigComponent\ComponentMetadata;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @experimental
 *
 * @internal
 */
class LiveComponentMetadata
{
    public function __construct(
        private ComponentMetadata $componentMetadata,
        /** @var LivePropMetadata[] */
        private array $livePropsMetadata,
    ) {
    }

    public function getComponentMetadata(): ComponentMetadata
    {
        return $this->componentMetadata;
    }

    /**
     * @return LivePropMetadata[]
     */
    public function getAllLivePropsMetadata(): array
    {
        return $this->livePropsMetadata;
    }

    public function getReadonlyPropPaths(): array
    {
        $writableProps = array_filter($this->livePropsMetadata, function ($livePropMetadata) {
            return !$livePropMetadata->isIdentityWritable();
        });

        return array_map(function ($livePropMetadata) {
            return $livePropMetadata->getName();
        }, $writableProps);
    }
}
