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
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * @author Antonio J. García Lagar <aj@garcialagar.es>
 */
#[AsLiveComponent('component_with_push_history_state', pushHistoryState: true)]
final class ComponentWithPushHistoryState
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: true)]
    public int $count = 0;

    #[LiveAction]
    public function setCount(#[LiveArg] int $count): void
    {
        $this->count = $count;
    }
}
