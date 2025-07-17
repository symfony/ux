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
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class ComponentWithRepeatedLiveListener
{
    use DefaultActionTrait;

    #[LiveListener('bar')]
    public function onBar(): void
    {
    }

    #[LiveListener('foo')]
    #[LiveListener('bar')]
    #[LiveListener('foo:bar')]
    public function onFooBar(): void
    {
    }
}
