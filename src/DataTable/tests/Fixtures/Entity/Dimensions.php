<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Dimensions
{
    public function __construct(
        #[ORM\Column]
        public int $width = 0,
    ) {
    }
}
