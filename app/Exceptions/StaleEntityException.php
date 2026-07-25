<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Thrown when a client submits a mutation against an entity version that is
 * no longer current (ADR-004, optimistic concurrency). Carries the freshly
 * loaded model so the HTTP layer can hand the client the current state
 * without a second round-trip.
 */
class StaleEntityException extends RuntimeException
{
    public function __construct(public readonly Model $entity)
    {
        parent::__construct('The entity has been modified since it was last read.');
    }

    /**
     * The minimal fresh state a client needs to reconcile after a conflict.
     *
     * @return array{type: string, id: int, version: int}
     */
    public function freshState(): array
    {
        return [
            'type' => class_basename($this->entity),
            'id' => (int) $this->entity->getKey(),
            'version' => (int) $this->entity->getAttribute('version'),
        ];
    }
}
