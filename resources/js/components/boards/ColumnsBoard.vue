<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    ChevronDown,
    ChevronUp,
    Pencil,
    Plus,
    Trash2,
} from '@lucide/vue';
import { ref } from 'vue';
import {
    destroy,
    reorder,
} from '@/actions/App/Http/Controllers/BoardColumnController';
import ColumnFormDialog from '@/components/boards/ColumnFormDialog.vue';
import TaskCard from '@/components/boards/TaskCard.vue';
import TaskFormDialog from '@/components/boards/TaskFormDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type {
    BoardAbilities,
    BoardColumn,
    BoardTask,
    WorkspaceMemberOption,
} from '@/types';

const props = defineProps<{
    workspaceId: number;
    boardId: number;
    columns: BoardColumn[];
    members: WorkspaceMemberOption[];
    can: Pick<BoardAbilities, 'createColumn' | 'reorderColumns'>;
}>();

const columnDialogOpen = ref(false);
const columnDialogMode = ref<'create' | 'edit'>('create');
const editingColumn = ref<BoardColumn | undefined>();

const taskDialogOpen = ref(false);
const taskDialogMode = ref<'create' | 'edit'>('create');
const taskColumnId = ref(0);
const editingTask = ref<BoardTask | undefined>();

function openCreateColumn() {
    columnDialogMode.value = 'create';
    editingColumn.value = undefined;
    columnDialogOpen.value = true;
}

function openEditColumn(column: BoardColumn) {
    columnDialogMode.value = 'edit';
    editingColumn.value = column;
    columnDialogOpen.value = true;
}

function openCreateTask(column: BoardColumn) {
    taskDialogMode.value = 'create';
    taskColumnId.value = column.id;
    editingTask.value = undefined;
    taskDialogOpen.value = true;
}

function openEditTask(column: BoardColumn, task: BoardTask) {
    taskDialogMode.value = 'edit';
    taskColumnId.value = column.id;
    editingTask.value = task;
    taskDialogOpen.value = true;
}

function deleteColumn(column: BoardColumn) {
    if (!window.confirm(`Delete the "${column.name}" column?`)) {
        return;
    }

    router.delete(
        destroy.url({
            workspace: props.workspaceId,
            board: props.boardId,
            column: column.id,
        }),
        { preserveScroll: true },
    );
}

function moveColumn(column: BoardColumn, direction: 'up' | 'down') {
    const ids = props.columns.map((item) => item.id);
    const index = ids.indexOf(column.id);
    const swapIndex = direction === 'up' ? index - 1 : index + 1;

    if (swapIndex < 0 || swapIndex >= ids.length) {
        return;
    }

    [ids[index], ids[swapIndex]] = [ids[swapIndex], ids[index]];

    router.patch(
        reorder.url({ workspace: props.workspaceId, board: props.boardId }),
        { columns: ids },
        { preserveScroll: true },
    );
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold">Columns</h2>
                <p class="text-sm text-muted-foreground">
                    Workflow stages for tasks on this board.
                </p>
            </div>
            <Button v-if="can.createColumn" @click="openCreateColumn">
                <Plus class="size-4" />
                Add column
            </Button>
        </div>

        <div class="flex gap-4 overflow-x-auto pb-2">
            <div
                v-for="(column, index) in columns"
                :key="column.id"
                class="flex w-72 shrink-0 flex-col rounded-xl border bg-muted/30"
            >
                <div class="flex items-start justify-between gap-2 border-b p-3">
                    <div class="min-w-0 space-y-1">
                        <h3 class="truncate font-medium">{{ column.name }}</h3>
                        <div class="flex flex-wrap gap-1.5">
                            <Badge
                                v-if="column.key"
                                variant="outline"
                                class="text-xs"
                            >
                                {{ column.key }}
                            </Badge>
                            <Badge
                                v-if="column.wip_limit"
                                variant="secondary"
                                class="text-xs"
                            >
                                WIP {{ column.wip_limit }}
                            </Badge>
                        </div>
                    </div>

                    <div
                        v-if="can.createColumn || can.reorderColumns"
                        class="flex shrink-0 items-center gap-0.5"
                    >
                        <template v-if="can.reorderColumns">
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                :disabled="index === 0"
                                @click="moveColumn(column, 'up')"
                            >
                                <ChevronUp class="size-4" />
                                <span class="sr-only">Move left</span>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                :disabled="index === columns.length - 1"
                                @click="moveColumn(column, 'down')"
                            >
                                <ChevronDown class="size-4" />
                                <span class="sr-only">Move right</span>
                            </Button>
                        </template>
                        <Button
                            v-if="can.createColumn"
                            variant="ghost"
                            size="icon-sm"
                            @click="openEditColumn(column)"
                        >
                            <Pencil class="size-4" />
                            <span class="sr-only">Edit column</span>
                        </Button>
                        <Button
                            v-if="can.createColumn"
                            variant="ghost"
                            size="icon-sm"
                            @click="deleteColumn(column)"
                        >
                            <Trash2 class="size-4 text-destructive" />
                            <span class="sr-only">Delete column</span>
                        </Button>
                    </div>
                </div>

                <div class="flex flex-1 flex-col gap-2 p-3">
                    <TaskCard
                        v-for="task in column.tasks ?? []"
                        :key="task.id"
                        :task="task"
                        :column="column"
                        :columns="columns"
                        :column-index="index"
                        :workspace-id="workspaceId"
                        :board-id="boardId"
                        @edit="openEditTask(column, $event)"
                    />

                    <Button
                        v-if="column.can?.createTask"
                        variant="ghost"
                        size="sm"
                        class="w-full justify-start text-muted-foreground"
                        @click="openCreateTask(column)"
                    >
                        <Plus class="size-4" />
                        Add task
                    </Button>
                </div>
            </div>
        </div>

        <ColumnFormDialog
            v-model:open="columnDialogOpen"
            :mode="columnDialogMode"
            :workspace-id="workspaceId"
            :board-id="boardId"
            :column="editingColumn"
        />

        <TaskFormDialog
            v-model:open="taskDialogOpen"
            :mode="taskDialogMode"
            :workspace-id="workspaceId"
            :board-id="boardId"
            :column-id="taskColumnId"
            :members="members"
            :task="editingTask"
        />
    </div>
</template>
