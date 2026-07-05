<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\ArchiveTask;
use App\Actions\Tasks\CreateTask;
use App\Actions\Tasks\DeleteTask;
use App\Actions\Tasks\MoveTask;
use App\Actions\Tasks\UpdateTask;
use App\Http\Requests\MoveTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Task;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TaskController extends Controller
{
    /**
     * Create a task in a column.
     */
    public function store(
        StoreTaskRequest $request,
        Workspace $workspace,
        Board $board,
        BoardColumn $column,
        CreateTask $action,
    ): RedirectResponse {
        Gate::authorize('create', [Task::class, $column]);

        $action->handle($column, $request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task created.')]);

        return to_route('workspaces.boards.show', [$workspace, $board]);
    }

    /**
     * Update a task.
     */
    public function update(
        UpdateTaskRequest $request,
        Workspace $workspace,
        Board $board,
        Task $task,
        UpdateTask $action,
    ): RedirectResponse {
        Gate::authorize('update', $task);

        $action->handle($task, $request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task updated.')]);

        return to_route('workspaces.boards.show', [$workspace, $board]);
    }

    /**
     * Move a task to another column and/or position.
     */
    public function move(
        MoveTaskRequest $request,
        Workspace $workspace,
        Board $board,
        Task $task,
        MoveTask $action,
    ): RedirectResponse {
        Gate::authorize('move', $task);

        $action->handle($task, $request->user(), $request->targetColumn(), $request->targetPosition());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task moved.')]);

        return to_route('workspaces.boards.show', [$workspace, $board]);
    }

    /**
     * Archive or restore a task.
     */
    public function archive(
        Request $request,
        Workspace $workspace,
        Board $board,
        Task $task,
        ArchiveTask $action,
    ): RedirectResponse {
        Gate::authorize('archive', $task);

        $archived = $request->boolean('archived', true);

        $action->handle($task, $request->user(), $archived);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $archived ? __('Task archived.') : __('Task restored.'),
        ]);

        return to_route('workspaces.boards.show', [$workspace, $board]);
    }

    /**
     * Soft-delete a task.
     */
    public function destroy(
        Request $request,
        Workspace $workspace,
        Board $board,
        Task $task,
        DeleteTask $action,
    ): RedirectResponse {
        Gate::authorize('delete', $task);

        $action->handle($task, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task deleted.')]);

        return to_route('workspaces.boards.show', [$workspace, $board]);
    }
}
