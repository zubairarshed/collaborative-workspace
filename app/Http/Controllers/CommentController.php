<?php

namespace App\Http\Controllers;

use App\Actions\Comments\CreateComment;
use App\Http\Requests\StoreCommentRequest;
use App\Models\Board;
use App\Models\Comment;
use App\Models\Task;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CommentController extends Controller
{
    /**
     * Add a comment to a task.
     */
    public function store(
        StoreCommentRequest $request,
        Workspace $workspace,
        Board $board,
        Task $task,
        CreateComment $action,
    ): RedirectResponse {
        Gate::authorize('create', [Comment::class, $task]);

        $action->handle($task, $request->user(), $request->validated('body'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Comment added.')]);

        return to_route('workspaces.boards.show', [$workspace, $board]);
    }
}
