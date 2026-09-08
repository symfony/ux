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

/**
 * @internal
 */
#[ORM\Entity]
class Team
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        public int $id,
    ) {
    }
}
