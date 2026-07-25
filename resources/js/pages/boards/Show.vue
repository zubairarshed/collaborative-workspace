<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Archive,
    ArchiveRestore,
    ArrowLeft,
    Pencil,
    Trash2,
} from '@lucide/vue';
import { ref } from 'vue';
import {
    archive,
    destroy,
    update,
} from '@/actions/App/Http/Controllers/BoardController';
import { show as showWorkspace } from '@/actions/App/Http/Controllers/WorkspaceController';
import ColumnsBoard from '@/components/boards/ColumnsBoard.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import RoleBadge from '@/components/workspaces/RoleBadge.vue';
import { dashboard } from '@/routes';
import type {
    BoardAbilities,
    BoardColumn,
    BoardDetail,
    BoardWorkspace,
    MembershipRole,
    WorkspaceMemberOption,
} from '@/types';

const props = defineProps<{
    workspace: BoardWorkspace;
    board: BoardDetail;
    columns: BoardColumn[];
    members: WorkspaceMemberOption[];
    role: MembershipRole;
    can: BoardAbilities;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Workspaces', href: dashboard() }],
    },
});

const editOpen = ref(false);
const editForm = useForm({
    name: props.board.name,
    description: props.board.description ?? '',
});

function submitEdit() {
    editForm
        .transform((data) => ({ ...data, version: props.board.version }))
        .put(
            update.url({
                workspace: props.workspace.id,
                board: props.board.slug,
            }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    editOpen.value = false;
                },
            },
        );
}

function toggleArchive() {
    const archiving = !props.board.is_archived;
    const message = archiving
        ? `Archive "${props.board.name}"? It will be hidden from the active boards list.`
        : `Restore "${props.board.name}" to active boards?`;

    if (!window.confirm(message)) {
        return;
    }

    router.patch(
        archive.url({ workspace: props.workspace.id, board: props.board.id }),
        { archived: archiving },
        { preserveScroll: true },
    );
}

function deleteBoard() {
    if (
        !window.confirm(`Delete "${props.board.name}"? This cannot be undone.`)
    ) {
        return;
    }

    router.delete(
        destroy.url({
            workspace: props.workspace.id,
            board: props.board.slug,
        }),
    );
}
</script>

<template>
    <Head :title="board.name" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-2">
                <Button variant="ghost" size="sm" class="-ml-2" as-child>
                    <Link :href="showWorkspace.url(workspace.id)">
                        <ArrowLeft class="size-4" />
                        Back to workspace
                    </Link>
                </Button>

                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-xl font-semibold tracking-tight">
                        {{ board.name }}
                    </h1>
                    <RoleBadge :role="role" />
                    <Badge v-if="board.is_archived" variant="secondary">
                        <Archive class="size-3" />
                        Archived
                    </Badge>
                </div>

                <p class="max-w-prose text-sm text-muted-foreground">
                    {{ board.description || 'No description provided.' }}
                </p>

                <p v-if="board.creator" class="text-xs text-muted-foreground">
                    Created by {{ board.creator.name }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Dialog v-if="can.update" v-model:open="editOpen">
                    <DialogTrigger as-child>
                        <Button variant="outline">
                            <Pencil class="size-4" />
                            Edit
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <form @submit.prevent="submitEdit">
                            <DialogHeader>
                                <DialogTitle>Edit board</DialogTitle>
                                <DialogDescription>
                                    Update the board name and description.
                                </DialogDescription>
                            </DialogHeader>

                            <div class="grid gap-4 py-4">
                                <div class="grid gap-2">
                                    <Label for="edit-board-name">Name</Label>
                                    <Input
                                        id="edit-board-name"
                                        v-model="editForm.name"
                                        required
                                    />
                                    <InputError
                                        :message="editForm.errors.name"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="edit-board-description">
                                        Description
                                    </Label>
                                    <Input
                                        id="edit-board-description"
                                        v-model="editForm.description"
                                    />
                                    <InputError
                                        :message="editForm.errors.description"
                                    />
                                </div>
                            </div>

                            <DialogFooter>
                                <Button
                                    type="submit"
                                    :disabled="editForm.processing"
                                >
                                    Save changes
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

                <Button
                    v-if="can.archive"
                    variant="outline"
                    @click="toggleArchive"
                >
                    <ArchiveRestore v-if="board.is_archived" class="size-4" />
                    <Archive v-else class="size-4" />
                    {{ board.is_archived ? 'Restore' : 'Archive' }}
                </Button>

                <Button
                    v-if="can.delete"
                    variant="outline"
                    @click="deleteBoard"
                >
                    <Trash2 class="size-4 text-destructive" />
                    Delete
                </Button>
            </div>
        </div>

        <ColumnsBoard
            :workspace-id="workspace.id"
            :board-id="board.id"
            :board-version="board.version"
            :columns="columns"
            :members="members"
            :can="{
                createColumn: can.createColumn,
                reorderColumns: can.reorderColumns,
            }"
        />
    </div>
</template>
