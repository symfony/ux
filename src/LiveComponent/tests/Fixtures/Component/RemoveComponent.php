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
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PreReRender;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveResponse;

#[AsLiveComponent('remove_component', template: 'components/remove_component.html.twig')]
class RemoveComponent
{
    use ComponentToolsTrait;
    use DefaultActionTrait;

    public static int $preReRenderCalls = 0;

    #[LiveProp(writable: true)]
    public int $count = 0;

    #[LiveAction]
    public function dismiss(): LiveResponse
    {
        ++$this->count;

        return LiveResponse::remove();
    }

    #[LiveAction]
    public function dismissWithEvents(): LiveResponse
    {
        $this->emit('componentRemoved', ['id' => 42]);
        $this->dispatchBrowserEvent('component:removed', ['id' => 42]);

        return LiveResponse::remove();
    }

    #[LiveAction]
    public function keep(): void
    {
        ++$this->count;
    }

    #[PreReRender]
    public function beforeReRender(): void
    {
        ++self::$preReRenderCalls;
    }
}
