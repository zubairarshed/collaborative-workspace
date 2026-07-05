<?php

namespace App\Http\Controllers;

use App\Actions\Boards\CreateColumn;
use App\Actions\Boards\DeleteColumn;
use App\Actions\Boards\ReorderColumns;
use App\Actions\Boards\UpdateColumn;
use App\Http\Requests\ReorderColumnsRequest;
use App\Http\Requests\StoreBoardColumnRequest;
use App\Http\Requests\UpdateBoardColumnRequest;
use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class BoardColumnController extends Controller
{
    /**
     * Add a column to a board.
     */
    public function store(StoreBoardColumnRequest $request, Workspace $workspace, Board $board, CreateColumn $action): RedirectResponse
    {
        Gate::authorize('create', [BoardColumn::class, $board]);

        $action->handle($board, $request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Column added.')]);

        return to_route('workspaces.boards.show', [$workspace, $board]);
    }

    /**
     * Update a column.
     */
    public function update(UpdateBoardColumnRequest $request, Workspace $workspace, Board $board, BoardColumn $column, UpdateColumn $action): RedirectResponse
    {
        Gate::authorize('update', $column);

        $action->handle($column, $request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Column updated.')]);

        return to_route('workspaces.boards.show', [$workspace, $board]);
    }

    /**
     * Reorder a board's columns.
     */
    public function reorder(ReorderColumnsRequest $request, Workspace $workspace, Board $board, ReorderColumns $action): RedirectResponse
    {
        Gate::authorize('reorder', [BoardColumn::class, $board]);

        $action->handle($board, $request->user(), $request->orderedColumnIds());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Columns reordered.')]);

        return to_route('workspaces.boards.show', [$workspace, $board]);
    }

    /**
     * Delete a column.
     */
    public function destroy(Request $request, Workspace $workspace, Board $board, BoardColumn $column, DeleteColumn $action): RedirectResponse
    {
        Gate::authorize('delete', $column);

        $action->handle($column, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Column deleted.')]);

        return to_route('workspaces.boards.show', [$workspace, $board]);
    }
}
