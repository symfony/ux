<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsLiveComponent('with_on_updated')]
final class WithOnUpdated
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, onUpdated: 'onNumber1Updated')]
    public int $number1;

    #[LiveProp]
    public int $number2;

    #[LiveProp]
    public int $number3;

    #[LiveProp]
    public int $total;

    #[PostMount]
    public function postMount(): void
    {
        $this->calculateTotal();
    }

    public function onNumber1Updated(): void
    {
        $this->calculateTotal();
    }

    private function calculateTotal(): void
    {
        // All live props must be available to read
        $this->total = $this->number1 + $this->number2 + $this->number3;
    }
}
