<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Command;

use Symfony\UX\TwigComponent\Command\TwigComponentDebugCommand;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LiveComponentDebugCommand extends TwigComponentDebugCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->setAliases(['debug:live-component']);
    }
}
