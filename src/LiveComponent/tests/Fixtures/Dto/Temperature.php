<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Dto;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Temperature
{
    public function __construct(public int $degrees, public string $uom)
    {
    }
}
