<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Navigation;

/**
 * @internal
 *
 * @author Simon André <smn.andre@gmail.com>
 */
enum QueryStringPolicy
{
    case Preserve;
    case Discard;
}
