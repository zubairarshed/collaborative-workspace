<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import { destroy } from '@/actions/App/Http/Controllers/TaskController';
import PriorityBadge from '@/components/boards/PriorityBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { BoardTask } from '@/types';

const props = defineProps<{
    task: BoardTask;
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
</script>

<template>
    <Card class="cursor-grab gap-3 py-3 shadow-sm active:cursor-grabbing">
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
                v-if="task.can.update || task.can.delete"
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
