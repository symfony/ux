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
use Symfony\UX\LiveComponent\Tests\Fixtures\Enum\IntEnum;
use Symfony\UX\LiveComponent\Tests\Fixtures\Enum\ZeroIntEnum;

#[AsLiveComponent('with_union_intersection_type')]
final class WithUnionIntersectionType
{
    use DefaultActionTrait;

    #[LiveProp]
    public int|float|null $unionProp = null;

    #[LiveProp]
    public IntEnum&ZeroIntEnum $intersectionProp;
}
