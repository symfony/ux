<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\DataCollector;

use Symfony\UX\TwigComponent\DataCollector\TwigComponentDataCollector;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class LiveComponentDataCollector extends TwigComponentDataCollector
{
    public function getName(): string
    {
        return 'live_component';
    }
}
