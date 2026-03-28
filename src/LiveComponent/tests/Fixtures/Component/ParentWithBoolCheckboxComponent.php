<?php

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('parent_with_bool_checkbox')]
final class ParentWithBoolCheckboxComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public bool $isActive = true;
}
