<?php

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('deferred_component', method: 'get')]
final class DeferredComponent
{
    use DefaultActionTrait;

    public function getLongAwaitedData(): string
    {
        return 'Long awaited data';
    }
}
