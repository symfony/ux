<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Enum;

/**
 * For convenience and performance, official UX Toolkit kits are hardcoded.
 *
 * @internal
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
enum ToolkitKitId: string
{
    case Shadcn = 'shadcn';
}
