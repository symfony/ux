# CHANGELOG

## 3.6.0

- Delegate Doctrine ORM cursor pagination to Doctrine's `CursorPaginator`, which now
  requires `doctrine/orm` 3.7 or higher
- Reject query parameters colliding with the parameters Doctrine generates for the
  cursor boundary predicate, instead of silently returning the wrong rows
- Replace Doctrine's deprecated `Paginator`, removed in ORM 4, with `OffsetPaginator`
  on the offset and lookahead paths. `OffsetPaginator` always runs a `COUNT` query, so
  a query joining a collection now costs one extra query per slice
- BC BREAK: Doctrine ORM cursor tokens issued by earlier versions are no longer valid
  and are rejected with an `InvalidCursorException`
- BC BREAK: an unsupported Doctrine type on a cursor field is now rejected when the
  order is resolved, so on the first page instead of the first navigation
- BC BREAK: a `firstResult` set on the source `QueryBuilder` is now ignored by cursor
  pagination, where it was previously applied as an `OFFSET`
- BC BREAK: cursor pagination over a `SELECT NEW` DTO query now requires the ordered
  fields to be public properties

## 3.5.1

- Add missing support for Symfony 8.2's standalone `AssetMapperBundle`

## 3.5.0

- Add the component
