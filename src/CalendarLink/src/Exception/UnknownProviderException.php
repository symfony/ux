<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\CalendarLink\Exception;

/**
 * @author Imad ZAIRIG <imadzairig@gmail.com>
 */
class UnknownProviderException extends InvalidArgumentException
{
    /**
     * @param list<string> $available
     */
    public function __construct(string $name, array $available)
    {
        parent::__construct(\sprintf(
            'Unknown calendar link provider "%s". Available providers: %s.',
            $name,
            $available ? '"'.implode('", "', $available).'"' : '(none registered)',
        ));
    }
}
