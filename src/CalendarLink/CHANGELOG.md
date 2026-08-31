# CHANGELOG

## 3.5

- Make `IcsBuilder` internal
- Serialize timed events in a named time zone with `;TZID=` and a matching `VTIMEZONE`, so recurring events no longer drift by an hour across DST
- Accept an optional `ClockInterface` in `IcsBuilder` so `DTSTAMP` is deterministic and testable
- Add an optional `uid` to `CalendarEvent`, and derive the ICS `UID` from the event content by default instead of a random value, so re-adding the same event no longer duplicates it

## 3.1

- Add component
