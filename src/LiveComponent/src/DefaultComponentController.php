<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 *
 * @internal
 */
final class DefaultComponentController
{
    private object $component;

    public function __construct(object $component)
    {
        $this->component = $component;
    }

    public function __invoke(): void
    {
    }

    public function getComponent(): object
    {
        return $this->component;
    }
}
