<?php

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('parent_with_checkbox_group')]
final class ParentWithCheckboxGroupComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public array $selected = ['a', 'c'];
}
