<?php

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[AsLiveComponent('alternate_route', route: 'alternate_live_route')]
final class AlternateRoute
{
    use DefaultActionTrait;

    #[LiveProp]
    public int $count = 0;

    #[LiveAction]
    public function increase(): void
    {
        ++$this->count;
    }
}
