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
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class LiveItemList
{
    use DefaultActionTrait;

    /**
     * @var string[]
     */
    #[LiveProp(writable: true, url: true)]
    public array $items = [];

    #[LiveAction]
    public function addItem(): void
    {
        $this->items[] = '';
    }

    #[LiveAction]
    public function deleteItems(): void
    {
        $this->items = [];
    }

    #[LiveAction]
    public function deleteItem(#[LiveArg] int $key): void
    {
        unset($this->items[$key]);
    }
}
