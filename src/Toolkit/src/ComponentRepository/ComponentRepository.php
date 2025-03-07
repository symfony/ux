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

use Symfony\Component\Finder\Finder;

/**
 * @author Jean-François Lépine
 *
 * @internal
 */
interface ComponentRepository
{
    public function fetch(RepositoryIdentity $repository): Finder;
}
