<?php

namespace App\Models;

use App\Models\Concerns\HasVersion;
use Database\Factories\BoardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['workspace_id', 'created_by', 'name', 'slug', 'description', 'is_archived', 'position'])]
class Board extends Model
{
    /** @use HasFactory<BoardFactory> */
    use HasFactory, HasVersion, SoftDeletes;

    /**
     * The default workflow columns created for every new board, in order.
     *
     * @var list<array{name: string, key: string}>
     */
    public const DEFAULT_COLUMNS = [
        ['name' => 'Todo', 'key' => 'todo'],
        ['name' => 'Doing', 'key' => 'doing'],
        ['name' => 'Review', 'key' => 'review'],
        ['name' => 'Done', 'key' => 'done'],
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_archived' => 'boolean',
            'position' => 'integer',
            'version' => 'integer',
        ];
    }

    /**
     * The workspace this board belongs to.
     *
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * The user who created this board.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The ordered workflow columns for this board.
     *
     * @return HasMany<BoardColumn, $this>
     */
    public function columns(): HasMany
    {
        return $this->hasMany(BoardColumn::class)->orderBy('position');
    }

    /**
     * Tasks belonging to this board.
     *
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
