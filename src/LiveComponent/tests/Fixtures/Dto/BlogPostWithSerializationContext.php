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

use Symfony\Component\Serializer\Annotation\Groups;

final class BlogPostWithSerializationContext
{
    public function __construct(
        #[Groups(['the_serialization_group'])]
        public string $title = '',
        #[Groups(['the_serialization_group'])]
        public string $body = '',
        public int $price = 0,
    ) {
    }
}
