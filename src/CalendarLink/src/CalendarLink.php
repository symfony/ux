<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\CalendarLink;

/**
 * @author Imad ZAIRIG <imadzairig@gmail.com>
 */
final class CalendarLink implements \Stringable
{
    public function __construct(
        public readonly string $provider,
        public readonly string $label,
        public readonly string $url,
    ) {
    }

    public function __toString(): string
    {
        return $this->url;
    }
}
