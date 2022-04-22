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

use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class BlogPost
{
    #[NotBlank(message: 'The title field should not be blank')]
    public $title;

    #[Length(min: 100, minMessage: 'The content field is too short')]
    public $content;

    public $comments = [];
}
