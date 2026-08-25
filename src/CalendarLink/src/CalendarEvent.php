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

use Symfony\Component\Uid\Uuid;
use Symfony\UX\CalendarLink\Exception\InvalidArgumentException;

/**
 * @author Imad ZAIRIG <imadzairig@gmail.com>
 */
final class CalendarEvent
{
    public readonly \DateTimeImmutable $start;
    public readonly \DateTimeImmutable $end;

    /**
     * @param list<CalendarReminder> $reminders
     * @param Uuid|null              $uid       A stable UID reused across builds so clients update the event instead of duplicating it; derived from the event content when null
     */
    public function __construct(
        public readonly string $title,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        public readonly ?string $description = null,
        public readonly ?string $location = null,
        public readonly bool $allDay = false,
        public readonly ?string $url = null,
        public readonly ?CalendarRecurrence $recurrence = null,
        public readonly array $reminders = [],
        public readonly ?Uuid $uid = null,
    ) {
        if ('' === trim($title)) {
            throw new InvalidArgumentException('Event title must not be empty.');
        }

        $this->start = \DateTimeImmutable::createFromInterface($start);
        $this->end = \DateTimeImmutable::createFromInterface($end);

        if ($this->end < $this->start) {
            throw new InvalidArgumentException(\sprintf('Event end "%s" must be on or after start "%s".', $this->end->format('c'), $this->start->format('c')));
        }
    }
}
