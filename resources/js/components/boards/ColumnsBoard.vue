<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { GripVertical, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import draggable from 'vuedraggable';
import {
    destroy,
    reorder,
} from '@/actions/App/Http/Controllers/BoardColumnController';
import { move } from '@/actions/App/Http/Controllers/TaskController';
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
    boardVersion: number;
    columns: BoardColumn[];
    members: WorkspaceMemberOption[];
    can: Pick<BoardAbilities, 'createColumn' | 'reorderColumns'>;
}>();

// A local, mutable copy of `columns` for vuedraggable's v-model. Re-synced
// whenever the prop changes (e.g. after any Inertia reload) so drag state
// never goes stale.
const localColumns = ref<BoardColumn[]>([]);

watch(
    () => props.columns,
    (columns) => {
        localColumns.value = columns.map((column) => ({
            ...column,
            tasks: [...(column.tasks ?? [])],
        }));
    },
    { immediate: true },
);

const columnDialogOpen = ref(false);
const columnDialogMode = ref<'create' | 'edit'>('create');
const editingColumn = ref<BoardColumn | undefined>();

const taskDialogOpen = ref(false);
const taskDialogMode = ref<'create' | 'edit'>('create');
const taskColumnId = ref(0);
const editingTaskId = ref<number | undefined>();

// Derived from the live `columns` prop (rather than a captured snapshot) so
// the dialog reflects fresh data — e.g. a newly added comment — after any
// Inertia reload while it's open.
const editingTask = computed<BoardTask | undefined>(() =>
    props.columns
        .find((column) => column.id === taskColumnId.value)
        ?.tasks?.find((task) => task.id === editingTaskId.value),
);

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
    editingTaskId.value = undefined;
    taskDialogOpen.value = true;
}

function openEditTask(column: BoardColumn, task: BoardTask) {
    taskDialogMode.value = 'edit';
    taskColumnId.value = column.id;
    editingTaskId.value = task.id;
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

function onColumnsReordered() {
    router.patch(
        reorder.url({ workspace: props.workspaceId, board: props.boardId }),
        {
            columns: localColumns.value.map((column) => column.id),
            // Column ordering is board-level state under ADR-004, so a
            // reorder is guarded by the board's version.
            version: props.boardVersion,
        },
        { preserveScroll: true },
    );
}

type DraggableChange = {
    added?: { element: BoardTask; newIndex: number };
    moved?: { element: BoardTask; oldIndex: number; newIndex: number };
    removed?: { element: BoardTask; oldIndex: number };
};

function onTaskListChanged(column: BoardColumn, event: DraggableChange) {
    const change = event.added ?? event.moved;

    if (!change) {
        return;
    }

    router.patch(
        move.url({
            workspace: props.workspaceId,
            board: props.boardId,
            task: change.element.id,
        }),
        {
            board_column_id: column.id,
            position: change.newIndex,
            version: change.element.version,
        },
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

        <draggable
            v-model="localColumns"
            item-key="id"
            tag="div"
            class="flex gap-4 overflow-x-auto pb-2"
            handle=".column-drag-handle"
            :disabled="!can.reorderColumns"
            @end="onColumnsReordered"
        >
            <template #item="{ element: column }">
                <div
                    class="flex w-72 shrink-0 flex-col rounded-xl border bg-muted/30"
                >
                    <div
                        class="flex items-start justify-between gap-2 border-b p-3"
                    >
                        <div class="flex min-w-0 items-start gap-1.5">
                            <GripVertical
                                v-if="can.reorderColumns"
                                class="column-drag-handle mt-0.5 size-4 shrink-0 cursor-grab text-muted-foreground"
                            />
                            <div class="min-w-0 space-y-1">
                                <h3 class="truncate font-medium">
                                    {{ column.name }}
                                </h3>
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
                        </div>

                        <div
                            v-if="can.createColumn"
                            class="flex shrink-0 items-center gap-0.5"
                        >
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                @click="openEditColumn(column)"
                            >
                                <Pencil class="size-4" />
                                <span class="sr-only">Edit column</span>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                @click="deleteColumn(column)"
                            >
                                <Trash2 class="size-4 text-destructive" />
                                <span class="sr-only">Delete column</span>
                            </Button>
                        </div>
                    </div>

                    <draggable
                        v-model="column.tasks"
                        item-key="id"
                        tag="div"
                        class="flex flex-1 flex-col gap-2 p-3"
                        group="tasks"
                        filter="button"
                        :prevent-on-filter="false"
                        :disabled="!column.can?.createTask"
                        @change="onTaskListChanged(column, $event)"
                    >
                        <template #item="{ element: task }">
                            <TaskCard
                                :task="task"
                                :workspace-id="workspaceId"
                                :board-id="boardId"
                                @edit="openEditTask(column, $event)"
                            />
                        </template>
                    </draggable>

                    <div class="px-3 pb-3">
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
            </template>
        </draggable>

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
