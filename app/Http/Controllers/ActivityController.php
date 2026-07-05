<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Workspace;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    /**
     * List a workspace's activity feed, newest first.
     */
    public function index(Workspace $workspace): Response
    {
        Gate::authorize('view', $workspace);

        $activities = $workspace->activities()
            ->with('causer')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Activity $activity) => [
                'id' => $activity->id,
                'message' => $activity->message,
                'causer' => $activity->causer ? [
                    'id' => $activity->causer->id,
                    'name' => $activity->causer->name,
                ] : null,
                'created_at' => $activity->created_at,
            ]);

        return Inertia::render('workspaces/Activity', [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
            ],
            'activities' => $activities,
        ]);
    }
}
