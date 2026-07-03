<?php

namespace App\Models;

use App\Enums\MembershipRole;
use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'description', 'owner_id'])]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Keep the owner's membership in sync with owner_id.
     */
    protected static function booted(): void
    {
        static::created(function (Workspace $workspace): void {
            if ($workspace->owner_id === null) {
                return;
            }

            $workspace->memberships()->updateOrCreate(
                ['user_id' => $workspace->owner_id],
                ['role' => MembershipRole::Owner, 'joined_at' => now()],
            );
        });
    }

    /**
     * The canonical owner of the workspace.
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Membership records for this workspace.
     *
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * Users that belong to this workspace via memberships.
     *
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'memberships')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Invitations issued for this workspace.
     *
     * @return HasMany<Invitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * Boards belonging to this workspace.
     *
     * @return HasMany<Board, $this>
     */
    public function boards(): HasMany
    {
        return $this->hasMany(Board::class);
    }

    /**
     * Tasks accessible through this workspace's boards.
     *
     * @return HasManyThrough<Task, Board, $this>
     */
    public function tasks(): HasManyThrough
    {
        return $this->hasManyThrough(Task::class, Board::class);
    }
}
