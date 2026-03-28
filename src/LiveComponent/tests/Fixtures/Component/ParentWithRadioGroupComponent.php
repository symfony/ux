<?php

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('parent_with_radio_group')]
final class ParentWithRadioGroupComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $selected = 'b';
}
