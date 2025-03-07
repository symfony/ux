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
enum RepositorySources: int
{
    public const int EMBEDDED = 1;
    public const int GITHUB = 2;
}
