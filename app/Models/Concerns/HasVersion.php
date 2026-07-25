<?php

namespace App\Models\Concerns;

use App\Exceptions\StaleEntityException;

/**
 * Optimistic-concurrency versioning (ADR-004).
 *
 * Actions re-fetch the row with lockForUpdate() inside their transaction,
 * assert the client-supplied version, and bump + save before commit. The
 * trait never saves by itself; the owning Action stays in control of when
 * the write happens.
 */
trait HasVersion
{
    /**
     * Throw when the client-supplied version no longer matches the row.
     */
    public function assertVersion(int $expected): void
    {
        if ($this->currentVersion() !== $expected) {
            throw new StaleEntityException($this);
        }
    }

    /**
     * Increment the version attribute. Saved by the Action, not the trait.
     */
    public function bumpVersion(): void
    {
        $this->setAttribute('version', $this->currentVersion() + 1);
    }

    public function currentVersion(): int
    {
        return (int) $this->getAttribute('version');
    }
}
