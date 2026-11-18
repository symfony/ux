<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

final class PostFormData
{
    #[Assert\Length(min: 10)]
    public ?string $title = null;

    public ?string $author = null;

    #[Assert\NotBlank]
    public ?PostCategory $category = null;

    public ?PostStatus $status = PostStatus::Draft;

    public ?bool $featured = false;
}
