<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Mathéo Daninos <matheo.daninos@gmail.com>
 */
class DataTableBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
