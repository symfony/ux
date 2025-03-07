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

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * @author Jean-François Lépine
 *
 * @internal
 */
class OfficialRepository implements ComponentRepository
{
    public function fetch(RepositoryIdentity $repository): Finder
    {
        $finder = new Finder();
        $fileystem = new Filesystem();

        if (! $fileystem->exists(__DIR__.'/../../registry/'.$repository->getPackage())) {
            throw new \InvalidArgumentException('This theme does not exist.');
        }

        $finder->in(\sprintf(__DIR__.'/../../registry/%s', $repository->getPackage()));

        return $finder;
    }
}
