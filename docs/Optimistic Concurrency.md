# Optimistic Concurrency (ADR-004) — Versioning Convention

Implemented in Sprint 8 Phase A. This documents the convention so future
entities can adopt it consistently.

## Which entities are versioned

Contended, user-editable state: `Task`, `BoardColumn`, `Board`. Each has an
`unsignedInteger('version')->default(1)` column (no index — versions are only
read via primary-key lookups).

Not versioned: `Comment` (append-only), `Activity`, `Notification`,
`Membership`, `Invitation`, and pivot tables.

## How it works

1. The board page serializes each entity's `version` to the client
   (`BoardController::show`), and the TS types (`BoardTask`, `BoardColumn`,
   `BoardDetail`) carry `version: number`.
2. Every mutating request (task update/move, column update, column reorder,
   board update) validates `'version' => ['required', 'integer', 'min:1']` in
   its Form Request and the controller passes it to the Action untouched.
3. Inside the Action's `DB::transaction()`, the entity is re-fetched with
   `lockForUpdate()`, `assertVersion($expected)` is called (throws
   `App\Exceptions\StaleEntityException` on mismatch), the existing logic
   runs unchanged, and `bumpVersion()` + save happens before commit. The
   trait `App\Models\Concerns\HasVersion` provides
   `assertVersion`/`bumpVersion`/`currentVersion`; it never saves by itself.
   Update Actions skip the bump when nothing actually changed (idempotent
   re-submits don't invalidate other clients).
4. `bootstrap/app.php` renders `StaleEntityException` as a JSON **409** whose
   body carries `entity: {type, id, version}` — the fresh state a client
   needs to reconcile without another round-trip.
5. Frontend: mutations are submitted through the Inertia router. A JSON 409
   is a non-Inertia response, so Inertia raises its `httpException` event;
   `resources/js/lib/staleEntity.ts` intercepts status 409, shows a toast,
   and partially reloads `['board', 'columns']`, which re-syncs the
   drag-and-drop state (`ColumnsBoard` rebuilds its local copy from props).
6. Domain events for versioned mutations expose a `public readonly int
   $version` (the NEW version) so Phase B realtime clients can reconcile.

## Conventions to follow for new entities

- **Version what you check.** An entity's version guards writes to that
  entity's own editable state.
- **Collection ordering belongs to the parent aggregate.** Reordering a
  board's columns checks and bumps the **Board** version (a reorder is a
  mutation of the board's layout, and the request carries one scalar
  `version`). Follow this for any new reorderable collection: guard it with
  the owning aggregate's version.
- **Delete/archive Actions do NOT check versions.** Operating on a deleted
  record 404s via the scoped route-model binding (soft-deleted models fall
  out of implicit binding), which satisfies ADR-004's "deleted → 404" rule.
- `version` is intentionally **not** in `$fillable` — it is only ever changed
  through `bumpVersion()`.
