<?php

namespace App\Models;

use App\Enums\ActivityType;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property ActivityType $type
 * @property array<string, mixed>|null $data
 * @property User|null $causer
 * @property string $message
 */
#[Fillable(['workspace_id', 'user_id', 'type', 'data'])]
class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'data' => 'array',
        ];
    }

    /**
     * The workspace this activity happened in.
     *
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * The user who performed the action, if any.
     *
     * @return BelongsTo<User, $this>
     */
    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The entity this activity is about.
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * A human-readable sentence describing this activity.
     *
     * Rendered entirely from the snapshotted `data` payload and the causer's
     * current name, so it stays correct even if the subject record itself
     * has since been hard-deleted (e.g. a removed membership).
     *
     * @return Attribute<string, never>
     */
    protected function message(): Attribute
    {
        return Attribute::get(fn (): string => $this->type->message($this));
    }
}
