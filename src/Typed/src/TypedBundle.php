<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Typed;

use Symfony\Component\HttpKernel\Bundle\Bundle;

trigger_deprecation('symfony/ux-typed', '2.27.0', 'The package is deprecated and will be removed in 3.0. Follow the migration steps in https://github.com/symfony/ux/tree/2.x/src/Typed to keep using Typed in your Symfony application.');

/**
 * @final
 */
class TypedBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
