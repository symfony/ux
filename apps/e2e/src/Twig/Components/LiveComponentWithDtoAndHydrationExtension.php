<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components;

use App\Model\Point;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class LiveComponentWithDtoAndHydrationExtension
{
    use DefaultActionTrait;

    #[LiveProp(url: true)]
    public ?Point $point = null;

    #[LiveAction]
    public function initPoint(): void
    {
        $this->point = Point::create(
            x: 69.420,
            y: -1.337,
        );
    }
}
