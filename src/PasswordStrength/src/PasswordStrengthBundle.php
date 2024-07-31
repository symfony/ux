<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\PasswordStrength;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @final
 */
class PasswordStrengthBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
