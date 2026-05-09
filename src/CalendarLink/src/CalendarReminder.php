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

use Symfony\UX\CalendarLink\Exception\InvalidArgumentException;

/**
 * @author Imad ZAIRIG <imadzairig@gmail.com>
 */
final class CalendarReminder
{
    private function __construct(
        public readonly int $minutesBefore,
        public readonly string $description = '',
    ) {
    }

    /**
     * @param 0|positive-int $weeks
     * @param 0|positive-int $days
     * @param 0|positive-int $hours
     * @param 0|positive-int $minutes
     */
    public static function before(
        int $weeks = 0,
        int $days = 0,
        int $hours = 0,
        int $minutes = 0,
        string $description = '',
    ): self {
        $total = $weeks * 10080 + $days * 1440 + $hours * 60 + $minutes;
        if ($total <= 0) {
            throw new InvalidArgumentException('Reminder total duration must be > 0.');
        }

        return new self($total, $description);
    }
}
