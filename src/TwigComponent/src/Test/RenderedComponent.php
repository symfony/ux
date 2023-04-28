<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Test;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RenderedComponent implements \Stringable
{
    /**
     * @internal
     */
    public function __construct(private string $html)
    {
    }

    public function __toString(): string
    {
        return $this->html;
    }
}
