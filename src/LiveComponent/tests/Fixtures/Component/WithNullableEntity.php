<?php

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\Entity1;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[AsLiveComponent('with_nullable_entity')]
final class WithNullableEntity
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?Entity1 $prop1 = null;
}
