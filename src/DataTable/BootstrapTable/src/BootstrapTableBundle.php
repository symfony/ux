<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\BootstrapTable;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Mathéo Daninos <mathéo.daninos@gmail.com>
 */
class BootstrapTableBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
