# Phase A — Optimistic Concurrency Control (Sprint 8, ADR-004)

Implement entity versioning + HTTP 409 conflict handling per `docs/System Architecture.md` (ADR-004: OCC, entity versioning yes, conflict response 409, deleted records 404). Follow all existing conventions in CLAUDE.md: business logic in Actions, controllers stay thin, side effects via Events/Listeners, Pest feature tests, PHPStan level 7 must pass.

Before writing any code, read the current implementations of: `app/Actions/Tasks/MoveTask.php`, `app/Actions/Tasks/UpdateTask.php`, the column reorder Action, the relevant Task/Board controllers and Form Requests, `app/Models/Task.php`, `bootstrap/app.php`, and the frontend board drag-drop code. Adapt to what actually exists — do not invent parallel structures.

## 1. Scope — which entities get versioning

Version these (contended, user-editable state):
- `Task` (moved/updated concurrently — highest priority)
- `BoardColumn` (reordering)
- `Board` (rename/update)

Do NOT version: `Comment` (append-only), `Activity`, `Notification`, `Membership`, `Invitation`, pivot tables (`task_user`, `label_task`).

## 2. Migration

One migration adding `version` to `tasks`, `board_columns`, `boards`:
- `$table->unsignedInteger('version')->default(1);` (place after `position` where applicable)
- No new index — version is only read via primary-key lookups.
- Must be safe on existing rows (default handles backfill).

## 3. Shared building blocks

- `app/Models/Concerns/HasVersion.php` trait with:
  - `assertVersion(int $expected): void` — throws `StaleEntityException` on mismatch
  - `bumpVersion(): void` — increments the attribute (saved by the Action, not the trait)
- `app/Exceptions/StaleEntityException.php` — carries the model instance so the handler can return fresh state.
- Apply the trait to Task, BoardColumn, Board. Add `version` to `$fillable`/casts as needed (int cast).

## 4. Action changes

For every Action that mutates a versioned entity — `MoveTask`, `UpdateTask`, `ReorderColumns`, `UpdateBoard` (and any other update/move Actions found for these entities):

Inside the EXISTING `DB::transaction()`:
1. Re-fetch the entity with `lockForUpdate()` (the version compare must be atomic with the write — OCC at the API layer, pessimistic lock only for the check-write window).
2. `assertVersion($expectedVersion)` using the version supplied by the client.
3. Perform the existing logic UNCHANGED — especially the temp-offset two-pass position shuffle in `MoveTask`/`ReorderColumns`. Do not restructure it.
4. `bumpVersion()` + save before commit.
5. Domain events fired after commit must include the NEW version in their payload (Phase B realtime clients will reconcile against it).

Delete/archive Actions: no version check. Per ADR-004, operating on a deleted record returns 404 — the scoped route-model binding already handles this; verify a test covers it.

`AssignTask` (pivot attach/detach): no version check needed unless assignment currently rewrites task columns — check the implementation first.

## 5. Validation layer

Add `version` to the relevant Form Requests (task update, task move, column reorder, board update):
- `'version' => ['required', 'integer', 'min:1']`
- Controllers pass it through to the Action; controllers gain NO logic.

## 6. Exception → HTTP 409

In `bootstrap/app.php` `->withExceptions()`: map `StaleEntityException` to a 409 response.
- Response body: fresh entity state (id, version, and the fields the client needs to reconcile) so no second round-trip is required.
- IMPORTANT — Inertia consideration: board mutations go through Inertia's router. A raw JSON 409 is not an idiomatic Inertia response. Inspect how the frontend currently submits moves/updates (Wayfinder actions + Inertia router vs axios/fetch) and choose accordingly:
  - If Inertia router: return 409 and handle it client-side via `onError`/global error handling, then trigger a partial reload of the board prop.
  - If plain XHR: JSON 409 body is fine.
  Document the choice in a code comment.

## 7. Frontend (Vue 3 + TypeScript)

- Add `version: number` to the Task/Column/Board TS types (wherever board page props are typed).
- Every move/update/reorder payload must include the entity's current `version`.
- On 409:
  1. Revert/discard the optimistic UI change.
  2. Partial Inertia reload of the board data (`only: ['board']` or equivalent existing pattern).
  3. Toast via the existing `flashToast.ts` / vue-sonner convention — message like "This item was updated by someone else. Board refreshed."
- Do not hand-edit generated Wayfinder files.
- Throttle-related code (drag persist-on-drop) stays as-is — persist on drop already matches ADR-004.

## 8. Tests (Pest, tests/Feature/…)

Use existing helpers `createWorkspaceFor()` / `addWorkspaceMember()`. Cover at minimum:
1. Update with correct version succeeds, version increments in DB.
2. Update with stale version → 409, entity unchanged, response contains fresh version.
3. Move task with stale version → 409, position/ordering unchanged.
4. Two sequential moves simulating two clients: second client using the pre-first-move version gets 409.
5. Column reorder stale version → 409.
6. Board update stale version → 409.
7. Mutating a deleted task → 404 (route binding), NOT 409.
8. Version is required by validation (422 when missing).
9. Activity/Notification listeners still fire on successful versioned updates (no regression).

## 9. Definition of done

- `composer test` passes (lint:check + phpstan level 7 + full Pest suite).
- `npm run types:check` and `npm run lint:check` pass.
- No business logic added to controllers.
- Temp-offset shuffle pattern untouched in behavior.
- Events now carry `version`; listeners unaffected otherwise.
- Brief note added to `docs/` or CLAUDE.md describing the versioning convention for future entities.
