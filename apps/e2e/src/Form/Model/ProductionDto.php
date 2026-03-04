<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Model;

class ProductionDto
{
    public ?string $type = null;

    public ?string $movieSearch = null;

    public ?string $videogameSearch = null;

    public ?string $title = null;
}
