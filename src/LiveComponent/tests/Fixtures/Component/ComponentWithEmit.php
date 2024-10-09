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
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\Entity1;

#[AsLiveComponent('component_with_emit')]
final class ComponentWithEmit
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    public $events = [];

    #[LiveAction]
    public function actionThatEmits(): void
    {
        $this->emit('event1', ['foo' => 'bar']);
        $this->events = $this->liveResponder->getEventsToEmit();
    }

    #[LiveAction]
    public function actionThatDispatchesABrowserEvent(): void
    {
        $this->liveResponder->dispatchBrowserEvent(
            'browser-event',
            ['fooKey' => 'barVal'],
        );
    }
}
