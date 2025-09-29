<?php

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Dto;

use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Tests\Fixtures\Enum\ColorEnum;

class DummyUsingPhpDoc
{
    /**
     * @var ColorEnum|null
     */
    #[LiveProp]
    public $color;
}
