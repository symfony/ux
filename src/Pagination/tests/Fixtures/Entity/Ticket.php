<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'tickets')]
class Ticket
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'ulid', unique: true)]
    private Ulid $reference;

    public function __construct(Uuid $id, Ulid $reference)
    {
        $this->id = $id;
        $this->reference = $reference;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getReference(): Ulid
    {
        return $this->reference;
    }
}
