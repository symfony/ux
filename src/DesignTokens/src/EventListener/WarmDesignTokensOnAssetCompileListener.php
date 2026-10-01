<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\EventListener;

use Symfony\Component\AssetMapper\Event\PreAssetsCompileEvent;
use Symfony\UX\DesignTokens\CacheWarmer\DesignTokensCacheWarmer;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class WarmDesignTokensOnAssetCompileListener
{
    public function __construct(
        private readonly DesignTokensCacheWarmer $warmer,
        private readonly string $buildDir,
    ) {
    }

    public function __invoke(PreAssetsCompileEvent $event): void
    {
        $event->getOutput()->writeln('Rendering the design token stylesheet...');

        $this->warmer->warmUp($this->buildDir, $this->buildDir);
    }
}
