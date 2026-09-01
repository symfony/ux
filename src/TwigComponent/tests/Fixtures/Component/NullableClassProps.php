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

#[AsTwigComponent]
class NullableClassProps
{
    public ?string $property = 'default';
    public ?string $mounted = null;

    public function mount(?string $mounted = 'default')
    {
        $this->mounted = $mounted;
    }
}
