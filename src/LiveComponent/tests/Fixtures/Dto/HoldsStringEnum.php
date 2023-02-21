<?php

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Dto;

use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\ProductFixtureEntity;
use Symfony\UX\LiveComponent\Tests\Fixtures\Enum\StringEnum;

class HoldsStringEnum
{
    public function __construct(public ?StringEnum $stringEnum)
    {
    }
}
