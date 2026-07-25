<?php

namespace App\Actions\Boards;

use App\Events\Boards\BoardUpdated;
use App\Models\Board;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateBoard
{
    /**
     * Update a board's editable attributes.
     *
     * The slug is intentionally left stable so existing links keep working.
     *
     * @param  array{name?: string, description?: string|null}  $data
     */
    public function handle(Board $board, User $actor, array $data, int $expectedVersion): Board
    {
        /** @var list<string> $changedFields */
        $changedFields = [];

        $board = DB::transaction(function () use ($board, $data, $expectedVersion, &$changedFields): Board {
            // OCC (ADR-004): re-read the row under a pessimistic lock so the
            // version compare stays atomic with the write.
            $board = Board::query()->lockForUpdate()->findOrFail($board->id);
            $board->assertVersion($expectedVersion);

            $board->fill(array_filter(
                [
                    'name' => $data['name'] ?? null,
                    'description' => $data['description'] ?? null,
                ],
                fn ($value, $key) => array_key_exists($key, $data),
                ARRAY_FILTER_USE_BOTH,
            ));

            $changedFields = array_keys($board->getDirty());

            if ($changedFields !== []) {
                $board->bumpVersion();
            }

            $board->save();

            return $board;
        });

        if ($changedFields !== []) {
            event(new BoardUpdated($board, $actor, $changedFields));
        }

        return $board;
    }
}
