# Todo List

- Add seeders
- Add cli for startup

## Security

- **Unparameterised user input reaches DQL in `TableViewRepository::fetchTableData()`** (`src/Core/TableView/Repository/TableViewRepository.php`).
  Everything below comes from the request body: `AjaxCrudController::tableHandler()` passes `$params['filter']` through untouched, so any
  authenticated backend user can craft it (`tableHandler` validates no CSRF token either).
  - Line ~52: `$qb->expr()->in('a.' . $key, implode(',', $value))` — array filter **values** are glued into DQL.
    `filter[status][]=1) OR 1=1 OR a.id IN(1` parses as valid DQL and bypasses the WHERE clause. Bind them instead:
    `->in('a.' . $key, ':' . $key)` + `setParameter($key, $value)`.
  - Lines ~55 / ~64 / ~156: `sprintf('a.%s = :%s', $key, $key)` — the filter **key** becomes the DQL field name *and* the parameter name.
  - Line ~28: `sprintf('a.%s BETWEEN :from AND :to', $field)` — `rangeFilters` field names arrive as JSON in the body.
  - Fix for the keys: validate each against the entity metadata (`$em->getClassMetadata(static::ENTITY)->getFieldNames()`) and drop unknown ones,
    rather than interpolating whatever was posted.
- **`CrudRepository::updateField()` hand-quotes values into DQL** (`src/Core/Repository/CrudRepository.php`, ~line 119):
  `if (!is_numeric($value)) { $value = "'" . $value . "'"; }` then `->set('a.' . $field, $value)`. Not HTTP-exposed in the apps today
  (A3S only calls it from Login with generated values), but it injects on the first caller that passes user input. Use a bound parameter,
  and validate `$field` against the entity metadata.

Notes: this is DQL rather than raw SQL, so the injected text still has to parse — filter bypass and boolean-based extraction are the realistic
outcomes, not arbitrary `UNION SELECT`. Post-authentication only. Found while writing the A3S backend test suite (2026-08-18).

## Noise

- **`Controller::setGlobalVariables()` reads session keys that may never have been written**
  (`src/Core/Controller/Controller.php`, ~lines 83-88). Once `loggedIn` is truthy it reads `loggedInEmail`,
  `loggedInFirstName`, `loggedInLastName`, `loggedInRole` and `tenantId` straight off the storage. `Login::login()`
  writes `tenantId` **only** when the user actually has a tenant, so every backend page render in a non-tenant app
  logs `Undefined array key "tenantId"`. Use `offsetExists()` first, or default with `?? null`. Surfaced by the A3S
  controller tests (2026-08-18).
