<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('scalar_types')]
final class ScalarTypes
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public int $int = 1;

    #[LiveProp(writable: true)]
    public float $float = 1.0;

    #[LiveProp(writable: true)]
    public bool $bool = true;

    #[LiveProp(writable: true)]
    public ?int $nullableInt = 1;

    #[LiveProp(writable: true)]
    public ?float $nullableFloat = 1.0;

    #[LiveProp(writable: true)]
    public ?bool $nullableBool = true;
}
