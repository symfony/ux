<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TogglePassword;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Félix Eymonot <felix.eymonot@alximy.io>
 */
final class TogglePasswordBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
