<?php

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[AsLiveComponent('component_with_array_prop')]
final class ComponentWithArrayProp
{
    use DefaultActionTrait;

    #[LiveProp]
    public array $prop = [];
}
