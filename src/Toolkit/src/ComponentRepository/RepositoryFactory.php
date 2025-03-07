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
final class RepositoryFactory
{
    public function __construct(
        private readonly OfficialRepository $officialRepository,
        private readonly GithubRepository $githubRepository,
    ) {
    }

    public function factory(RepositoryIdentity $repository): ComponentRepository
    {
        switch ($repository->getType()) {
            case RepositorySources::EMBEDDED:
                return $this->officialRepository;
            case RepositorySources::GITHUB:
                return $this->githubRepository;
        }

        throw new \InvalidArgumentException('Source is not supported for this component.');
    }
}
