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

/**
 * @author Bart Vanderstukken <bart.vanderstukken@gmail.com>
 */
#[AsTwigComponent]
final class DivComponent5
{
    public string $divComponentName = 'foo';

    public function someFunction(): string
    {
        return 'calling DivComponent';
    }
}
