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

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class LiveCounter
{
    use DefaultActionTrait;

    #[LiveProp(url: true)]
    public int $value = 0;

    #[LiveAction]
    public function decrement(): void
    {
        --$this->value;
    }

    #[LiveAction]
    public function increment(): void
    {
        ++$this->value;
    }
}
