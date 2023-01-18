<?php

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Dto;

use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\ProductFixtureEntity;

class HoldsDateAndEntity
{
    public function __construct(public \DateTime $createdAt, public ProductFixtureEntity $product)
    {
    }
}
