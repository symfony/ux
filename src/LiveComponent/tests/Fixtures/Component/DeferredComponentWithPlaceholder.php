<?php

declare(strict_types=1);

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('deferred_component_with_placeholder', method: 'get')]
final class DeferredComponentWithPlaceholder
{
    use DefaultActionTrait;

    #[LiveProp]
    public int $rows = 6;
}
