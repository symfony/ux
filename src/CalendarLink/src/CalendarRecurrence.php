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
final class CalendarRecurrence
{
    private function __construct(
        public readonly string $rrule,
    ) {
    }

    public static function minutely(int $interval = 1, ?int $count = null, ?\DateTimeInterface $until = null): self
    {
        return self::fromParts('MINUTELY', $interval, $count, $until);
    }

    public static function daily(int $interval = 1, ?int $count = null, ?\DateTimeInterface $until = null): self
    {
        return self::fromParts('DAILY', $interval, $count, $until);
    }

    public static function weekly(int $interval = 1, ?int $count = null, ?\DateTimeInterface $until = null): self
    {
        return self::fromParts('WEEKLY', $interval, $count, $until);
    }

    public static function monthly(int $interval = 1, ?int $count = null, ?\DateTimeInterface $until = null): self
    {
        return self::fromParts('MONTHLY', $interval, $count, $until);
    }

    public static function yearly(int $interval = 1, ?int $count = null, ?\DateTimeInterface $until = null): self
    {
        return self::fromParts('YEARLY', $interval, $count, $until);
    }

    private static function fromParts(string $frequency, int $interval, ?int $count, ?\DateTimeInterface $until): self
    {
        if ($interval < 1) {
            throw new InvalidArgumentException(\sprintf('Recurrence "interval" must be >= 1, got %d.', $interval));
        }

        if (null !== $count && null !== $until) {
            throw new InvalidArgumentException('Recurrence "count" and "until" are mutually exclusive.');
        }

        if (null !== $count && $count < 1) {
            throw new InvalidArgumentException(\sprintf('Recurrence "count" must be >= 1, got %d.', $count));
        }

        $parts = ['FREQ='.$frequency];

        if (1 !== $interval) {
            $parts[] = 'INTERVAL='.$interval;
        }

        if (null !== $count) {
            $parts[] = 'COUNT='.$count;
        }

        if (null !== $until) {
            $parts[] = 'UNTIL='.\DateTimeImmutable::createFromInterface($until)
                ->setTimezone(new \DateTimeZone('UTC'))
                ->format('Ymd\THis\Z');
        }

        return new self(implode(';', $parts));
    }
}
