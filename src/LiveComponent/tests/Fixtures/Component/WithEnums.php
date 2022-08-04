<?php

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Tests\Fixtures\Enum\EmptyStringEnum;
use Symfony\UX\LiveComponent\Tests\Fixtures\Enum\IntEnum;
use Symfony\UX\LiveComponent\Tests\Fixtures\Enum\StringEnum;
use Symfony\UX\LiveComponent\Tests\Fixtures\Enum\ZeroIntEnum;

#[AsLiveComponent('with_enum')]
final class WithEnums
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public ?IntEnum $int = null;

    #[LiveProp(writable: true)]
    public ?StringEnum $string = null;

    #[LiveProp(writable: true)]
    public ?ZeroIntEnum $zeroInt = null;

    #[LiveProp(writable: true)]
    public ?EmptyStringEnum $emptyString = null;
}
