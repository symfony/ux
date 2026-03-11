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
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Parent component that:
 *  - uses HasLivePropTrait (contains a public #[LiveProp(fieldName: callable)])
 *  - declares a #[LiveAction] + #[LiveListener] on a method
 *
 * Child classes that extend this without redeclaring the trait or the method
 * must still have the LiveProp registered with the correct fieldName, and the
 * action/listener discoverable.
 */
#[AsLiveComponent]
class LivePropInheritanceParent
{
    use DefaultActionTrait;
    use HasLivePropTrait;

    #[LiveAction]
    #[LiveListener('save')]
    public function save(): void
    {
        // no-op for testing
    }
}
