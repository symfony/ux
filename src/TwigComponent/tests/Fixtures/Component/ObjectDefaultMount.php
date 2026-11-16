<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Fixtures\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('object_default_mount')]
final class ObjectDefaultMount
{
    public ?object $fresh = null;

    public function mount(object $fresh = new \stdClass()): void
    {
        $this->fresh = $fresh;
    }
}
