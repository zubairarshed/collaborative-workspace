<script setup lang="ts">
import { computed } from 'vue';
import BoardCard from '@/components/boards/BoardCard.vue';
import CreateBoardDialog from '@/components/boards/CreateBoardDialog.vue';
import type { BoardListItem } from '@/types';

const { workspaceId, boards, canCreate } = defineProps<{
    workspaceId: number;
    boards: BoardListItem[];
    canCreate: boolean;
}>();

const activeBoards = computed(() =>
    boards.filter((board) => !board.is_archived),
);

const archivedBoards = computed(() =>
    boards.filter((board) => board.is_archived),
);
</script>

<template>
    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold">Boards</h2>
                <p class="text-sm text-muted-foreground">
                    {{ boards.length }}
                    {{ boards.length === 1 ? 'board' : 'boards' }} in this
                    workspace
                </p>
            </div>
            <CreateBoardDialog v-if="canCreate" :workspace-id="workspaceId" />
        </div>

        <div
            v-if="activeBoards.length === 0 && archivedBoards.length === 0"
            class="rounded-xl border border-dashed p-8 text-center"
        >
            <p class="text-sm text-muted-foreground">
                No boards yet.
                <template v-if="canCreate">
                    Create one to start organizing work.
                </template>
            </p>
        </div>

        <div
            v-if="activeBoards.length > 0"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
            <BoardCard
                v-for="board in activeBoards"
                :key="board.id"
                :workspace-id="workspaceId"
                :board="board"
            />
        </div>

        <div v-if="archivedBoards.length > 0" class="flex flex-col gap-3">
            <h3 class="text-sm font-medium text-muted-foreground">
                Archived ({{ archivedBoards.length }})
            </h3>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <BoardCard
                    v-for="board in archivedBoards"
                    :key="board.id"
                    :workspace-id="workspaceId"
                    :board="board"
                />
            </div>
        </div>
    </div>
</template>
