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
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class UuidIdEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    public Uuid $id;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }
}
