<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Regeneration;

use Symfony\UX\Image\Exception\RuntimeException;

final class RegenerationServiceResolver
{
    /** @var list<ImageAssetProviderInterface> */
    private array $providers;

    /** @var list<ImageAssetPersisterInterface> */
    private array $persisters;

    /**
     * @param iterable<ImageAssetProviderInterface>  $providers
     * @param iterable<ImageAssetPersisterInterface> $persisters
     */
    public function __construct(iterable $providers, iterable $persisters)
    {
        $this->providers = [];
        foreach ($providers as $provider) {
            $this->providers[] = $provider;
        }
        $this->persisters = [];
        foreach ($persisters as $persister) {
            $this->persisters[] = $persister;
        }
    }

    public function provider(): ImageAssetProviderInterface
    {
        if (1 !== \count($this->providers)) {
            throw new RuntimeException(\sprintf('UX Image regeneration requires exactly one ImageAssetProviderInterface service; found %d.', \count($this->providers)));
        }

        return $this->providers[0];
    }

    public function persister(): ImageAssetPersisterInterface
    {
        if (1 !== \count($this->persisters)) {
            throw new RuntimeException(\sprintf('UX Image regeneration requires exactly one ImageAssetPersisterInterface service; found %d.', \count($this->persisters)));
        }

        return $this->persisters[0];
    }
}
