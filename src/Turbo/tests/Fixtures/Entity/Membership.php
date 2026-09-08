<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;

/**
 * An entity whose primary key is composed of two associations, so Doctrine
 * stores the related entities themselves in the identifier properties.
 *
 * @internal
 */
#[ORM\Entity]
#[Broadcast]
class Membership
{
    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: Player::class)]
        public Player $player,
        #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: Team::class)]
        public Team $team,
        #[ORM\Column]
        public string $role = 'member',
    ) {
    }
}
