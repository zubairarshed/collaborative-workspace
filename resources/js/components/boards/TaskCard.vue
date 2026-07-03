<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowLeft,
    ArrowRight,
    ArrowUp,
    Pencil,
    Trash2,
} from '@lucide/vue';
import { destroy, move } from '@/actions/App/Http/Controllers/TaskController';
import PriorityBadge from '@/components/boards/PriorityBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { BoardColumn, BoardTask } from '@/types';

const props = defineProps<{
    task: BoardTask;
    column: BoardColumn;
    columns: BoardColumn[];
    columnIndex: number;
    workspaceId: number;
    boardId: number;
}>();

const emit = defineEmits<{
    edit: [task: BoardTask];
}>();

function formatDueDate(value: string | null): string | null {
    if (!value) {
        return null;
    }

    return new Date(value).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });
}

function deleteTask() {
    if (!window.confirm(`Delete "${props.task.title}"?`)) {
        return;
    }

    router.delete(
        destroy.url({
            workspace: props.workspaceId,
            board: props.boardId,
            task: props.task.id,
        }),
        { preserveScroll: true },
    );
}

function moveTask(position: number, boardColumnId: number) {
    router.patch(
        move.url({
            workspace: props.workspaceId,
            board: props.boardId,
            task: props.task.id,
        }),
        {
            board_column_id: boardColumnId,
            position,
        },
        { preserveScroll: true },
    );
}

function moveWithinColumn(direction: 'up' | 'down') {
    const tasks = props.column.tasks ?? [];
    const index = tasks.findIndex((item) => item.id === props.task.id);
    const targetIndex = direction === 'up' ? index - 1 : index + 1;

    if (targetIndex < 0 || targetIndex >= tasks.length) {
        return;
    }

    moveTask(targetIndex, props.column.id);
}

function moveToAdjacentColumn(direction: 'left' | 'right') {
    const targetIndex =
        direction === 'left' ? props.columnIndex - 1 : props.columnIndex + 1;
    const targetColumn = props.columns[targetIndex];

    if (!targetColumn) {
        return;
    }

    const targetPosition = targetColumn.tasks?.length ?? 0;
    moveTask(targetPosition, targetColumn.id);
}
</script>

<template>
    <Card class="gap-3 py-3 shadow-sm">
        <CardHeader class="gap-2 px-3">
            <div class="flex items-start justify-between gap-2">
                <CardTitle class="text-sm leading-snug font-medium">
                    {{ task.title }}
                </CardTitle>
                <PriorityBadge :priority="task.priority" />
            </div>
        </CardHeader>

        <CardContent class="flex flex-col gap-3 px-3">
            <p
                v-if="task.description"
                class="line-clamp-2 text-xs text-muted-foreground"
            >
                {{ task.description }}
            </p>

            <div class="flex flex-wrap gap-2 text-xs text-muted-foreground">
                <span v-if="task.assignee">
                    {{ task.assignee.name }}
                </span>
                <span v-if="formatDueDate(task.due_at)">
                    Due {{ formatDueDate(task.due_at) }}
                </span>
            </div>

            <div
                v-if="task.can.update || task.can.move || task.can.delete"
                class="flex flex-wrap items-center gap-0.5"
            >
                <Button
                    v-if="task.can.update"
                    variant="ghost"
                    size="icon-sm"
                    @click="emit('edit', task)"
                >
                    <Pencil class="size-3.5" />
                    <span class="sr-only">Edit task</span>
                </Button>
                <template v-if="task.can.move">
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        :disabled="columnIndex === 0"
                        @click="moveToAdjacentColumn('left')"
                    >
                        <ArrowLeft class="size-3.5" />
                        <span class="sr-only">Move to previous column</span>
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        :disabled="columnIndex === columns.length - 1"
                        @click="moveToAdjacentColumn('right')"
                    >
                        <ArrowRight class="size-3.5" />
                        <span class="sr-only">Move to next column</span>
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        :disabled="
                            (column.tasks?.findIndex(
                                (item) => item.id === task.id,
                            ) ?? 0) === 0
                        "
                        @click="moveWithinColumn('up')"
                    >
                        <ArrowUp class="size-3.5" />
                        <span class="sr-only">Move up</span>
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        :disabled="
                            (column.tasks?.findIndex(
                                (item) => item.id === task.id,
                            ) ?? 0) ===
                            (column.tasks?.length ?? 1) - 1
                        "
                        @click="moveWithinColumn('down')"
                    >
                        <ArrowDown class="size-3.5" />
                        <span class="sr-only">Move down</span>
                    </Button>
                </template>
                <Button
                    v-if="task.can.delete"
                    variant="ghost"
                    size="icon-sm"
                    @click="deleteTask"
                >
                    <Trash2 class="size-3.5 text-destructive" />
                    <span class="sr-only">Delete task</span>
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
