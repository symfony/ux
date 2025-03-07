<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\ComponentRepository;

/**
 * @author Jean-François Lépine
 *
 * @internal
 */
final class CurrentTheme
{
    private ComponentRepository $repository;
    private RepositoryIdentity $identity;

    public function __construct(
        string $theme,
        RepositoryFactory $repositoryFactory,
        RepositoryIdentifier $repositoryIdentifier,
    ) {
        $this->identity = $repositoryIdentifier->identify($theme);
        $this->repository = $repositoryFactory->factory($this->identity);
    }

    public function getRepository(): ComponentRepository
    {
        return $this->repository;
    }

    public function getIdentity(): RepositoryIdentity
    {
        return $this->identity;
    }
}
