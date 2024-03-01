<?php

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Dto;

class ParentDTO
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var ChildDTO|null
     */
    public $child;
}