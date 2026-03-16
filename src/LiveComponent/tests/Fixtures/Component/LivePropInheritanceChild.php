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

/**
 * Child component that extends LivePropInheritanceParent without redeclaring:
 *  - HasLivePropTrait
 *  - save() method with #[LiveAction] and #[LiveListener]
 *
 * All of those must still be discoverable on this class.
 */
#[AsLiveComponent]
class LivePropInheritanceChild extends LivePropInheritanceParent
{
    // Intentionally empty: does NOT redeclare the trait or override save().
}
