<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components\Live;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class FeaturedToggle
{
    use DefaultActionTrait;

    #[LiveProp]
    public int $postId;

    #[LiveProp(updateFromParent: true)]
    public bool $featured;

    #[LiveAction]
    public function toggle(): void
    {
        $this->featured = !$this->featured;
    }
}
