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

#[ORM\Entity]
class ForeignKeyIdEntity
{
    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne(cascade: ['persist'])]
        public Entity1 $id,
    ) {
    }
}
