<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\LiveComponent\Tests\Fixtures\Dto\Embeddable2;

#[ORM\Entity]
class Entity2
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public $id;

    #[ORM\Embedded(Embeddable1::class)]
    public Embeddable1 $embedded1;

    #[ORM\Embedded(Embeddable2::class)]
    public Embeddable2 $embedded2;
}
