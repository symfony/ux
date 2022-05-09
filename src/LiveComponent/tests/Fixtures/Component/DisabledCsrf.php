<?php

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * @author Tomas Norkūnas <norkunas.tom@gmail.com>
 */
#[AsLiveComponent('disabled_csrf', defaultAction: 'defaultAction()', csrf: false)]
final class DisabledCsrf
{
    #[LiveProp]
    public int $count = 1;

    #[LiveAction]
    public function increase(): void
    {
        ++$this->count;
    }

    public function defaultAction(): void
    {
    }
}
