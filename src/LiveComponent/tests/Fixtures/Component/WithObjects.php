<?php

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Tests\Fixtures\Dto\Embeddable2;
use Symfony\UX\LiveComponent\Tests\Fixtures\Dto\Money;
use Symfony\UX\LiveComponent\Tests\Fixtures\Dto\Temperature;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\Embeddable1;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\Entity1;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\Entity2;

#[AsLiveComponent('with_objects')]
final class WithObjects
{
    use DefaultActionTrait;

    #[LiveProp]
    public Money $money;

    #[LiveProp]
    public Temperature $temperature;

    #[LiveProp]
    public Entity1 $entity1;

    #[LiveProp]
    public Entity2 $entity2;

    #[LiveProp]
    public Embeddable1 $embeddable1;

    #[LiveProp]
    public Embeddable2 $embeddable2;
}
