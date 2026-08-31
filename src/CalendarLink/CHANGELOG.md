# CHANGELOG

## 3.5

- Make `IcsBuilder` internal
- Serialize timed events in a named time zone with `;TZID=` and a matching `VTIMEZONE`, so recurring events no longer drift by an hour across DST
- Accept an optional `ClockInterface` in `IcsBuilder` so `DTSTAMP` is deterministic and testable

## 3.1

- Add component
