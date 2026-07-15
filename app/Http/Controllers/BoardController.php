<?php

namespace App\Http\Controllers;

use App\Actions\Boards\ArchiveBoard;
use App\Actions\Boards\CreateBoard;
use App\Actions\Boards\DeleteBoard;
use App\Actions\Boards\UpdateBoard;
use App\Http\Requests\StoreBoardRequest;
use App\Http\Requests\UpdateBoardRequest;
use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Task;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BoardController extends Controller
{
    /**
     * Create a board inside a workspace.
     */
    public function store(StoreBoardRequest $request, Workspace $workspace, CreateBoard $action): RedirectResponse
    {
        Gate::authorize('create', [Board::class, $workspace]);

        $board = $action->handle($workspace, $request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Board created.')]);

        return to_route('workspaces.boards.show', [$workspace, $board]);
    }

    /**
     * Show a board with its columns.
     */
    public function show(Request $request, Workspace $workspace, Board $board): Response
    {
        Gate::authorize('view', $board);

        $user = $request->user();

        $board->load([
            'creator',
            'columns.tasks' => fn ($query) => $query
                ->where('is_archived', false)
                ->with(['assignee', 'creator', 'comments.author'])
                ->orderBy('position'),
        ]);

        $members = $workspace->members()
            ->orderBy('name')
            ->get()
            ->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
            ]);

        return Inertia::render('boards/Show', [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
            ],
            'board' => [
                'id' => $board->id,
                'name' => $board->name,
                'slug' => $board->slug,
                'description' => $board->description,
                'is_archived' => $board->is_archived,
                'position' => $board->position,
                'version' => $board->version,
                'created_at' => $board->created_at,
                'creator' => $board->creator ? [
                    'id' => $board->creator->id,
                    'name' => $board->creator->name,
                ] : null,
            ],
            'columns' => $board->columns->map(fn (BoardColumn $column) => [
                'id' => $column->id,
                'name' => $column->name,
                'key' => $column->key,
                'position' => $column->position,
                'wip_limit' => $column->wip_limit,
                'version' => $column->version,
                'can' => [
                    'createTask' => $user->can('create', [Task::class, $column]),
                ],
                'tasks' => $column->tasks->map(fn (Task $task) => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'priority' => $task->priority->value,
                    'due_at' => $task->due_at,
                    'position' => $task->position,
                    'version' => $task->version,
                    'assignee' => $task->assignee ? [
                        'id' => $task->assignee->id,
                        'name' => $task->assignee->name,
                    ] : null,
                    'creator' => $task->creator ? [
                        'id' => $task->creator->id,
                        'name' => $task->creator->name,
                    ] : null,
                    'comments' => $task->comments->map(fn ($comment) => [
                        'id' => $comment->id,
                        'body' => $comment->body,
                        'created_at' => $comment->created_at,
                        'author' => $comment->author ? [
                            'id' => $comment->author->id,
                            'name' => $comment->author->name,
                        ] : null,
                    ]),
                    'can' => [
                        'update' => $user->can('update', $task),
                        'move' => $user->can('move', $task),
                        'archive' => $user->can('archive', $task),
                        'delete' => $user->can('delete', $task),
                    ],
                ]),
            ]),
            'members' => $members,
            'role' => $user->roleIn($workspace),
            'can' => [
                'update' => $user->can('update', $board),
                'archive' => $user->can('archive', $board),
                'delete' => $user->can('delete', $board),
                'createColumn' => $user->can('create', [BoardColumn::class, $board]),
                'reorderColumns' => $user->can('reorder', [BoardColumn::class, $board]),
            ],
        ]);
    }

    /**
     * Update board settings.
     */
    public function update(UpdateBoardRequest $request, Workspace $workspace, Board $board, UpdateBoard $action): RedirectResponse
    {
        Gate::authorize('update', $board);

        $action->handle($board, $request->user(), $request->safe()->except('version'), $request->integer('version'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Board updated.')]);

        return to_route('workspaces.boards.show', [$workspace, $board]);
    }

    /**
     * Archive or restore a board.
     */
    public function archive(Request $request, Workspace $workspace, Board $board, ArchiveBoard $action): RedirectResponse
    {
        Gate::authorize('archive', $board);

        $archived = $request->boolean('archived', true);

        $action->handle($board, $request->user(), $archived);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $archived ? __('Board archived.') : __('Board restored.'),
        ]);

        return to_route('workspaces.boards.show', [$workspace, $board]);
    }

    /**
     * Soft-delete a board.
     */
    public function destroy(Request $request, Workspace $workspace, Board $board, DeleteBoard $action): RedirectResponse
    {
        Gate::authorize('delete', $board);

        $action->handle($board, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Board deleted.')]);

        return to_route('workspaces.show', $workspace);
    }
}
