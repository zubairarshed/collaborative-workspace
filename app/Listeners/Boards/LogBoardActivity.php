<?php

namespace App\Listeners\Boards;

use App\Actions\Activities\RecordActivity;
use App\Enums\ActivityType;
use App\Events\Boards\BoardArchivalToggled;
use App\Events\Boards\BoardCreated;
use App\Events\Boards\BoardDeleted;
use App\Events\Boards\BoardUpdated;
use App\Events\Boards\ColumnCreated;
use App\Events\Boards\ColumnDeleted;
use App\Events\Boards\ColumnsReordered;
use App\Events\Boards\ColumnUpdated;

class LogBoardActivity
{
    public function __construct(private readonly RecordActivity $recordActivity) {}

    public function handleBoardCreated(BoardCreated $event): void
    {
        $this->recordActivity->handle($event->board->workspace, $event->actor, ActivityType::BoardCreated, $event->board);
    }

    public function handleBoardUpdated(BoardUpdated $event): void
    {
        $this->recordActivity->handle($event->board->workspace, $event->actor, ActivityType::BoardUpdated, $event->board, [
            'fields' => $event->changedFields,
        ]);
    }

    public function handleBoardArchivalToggled(BoardArchivalToggled $event): void
    {
        $this->recordActivity->handle($event->board->workspace, $event->actor, ActivityType::BoardArchivalToggled, $event->board, [
            'archived' => $event->archived,
        ]);
    }

    public function handleBoardDeleted(BoardDeleted $event): void
    {
        $this->recordActivity->handle($event->board->workspace, $event->actor, ActivityType::BoardDeleted, $event->board);
    }

    public function handleColumnCreated(ColumnCreated $event): void
    {
        $this->recordActivity->handle($event->column->board->workspace, $event->actor, ActivityType::ColumnCreated, $event->column);
    }

    public function handleColumnUpdated(ColumnUpdated $event): void
    {
        $this->recordActivity->handle($event->column->board->workspace, $event->actor, ActivityType::ColumnUpdated, $event->column, [
            'fields' => $event->changedFields,
        ]);
    }

    public function handleColumnDeleted(ColumnDeleted $event): void
    {
        $this->recordActivity->handle($event->board->workspace, $event->actor, ActivityType::ColumnDeleted, $event->column);
    }

    public function handleColumnsReordered(ColumnsReordered $event): void
    {
        $this->recordActivity->handle($event->board->workspace, $event->actor, ActivityType::ColumnsReordered, $event->board);
    }
}
