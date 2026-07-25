<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { History, Pencil, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import { index as activityIndex } from '@/actions/App/Http/Controllers/ActivityController';
import {
    destroy,
    update,
} from '@/actions/App/Http/Controllers/WorkspaceController';
import BoardsList from '@/components/boards/BoardsList.vue';
import InputError from '@/components/InputError.vue';
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
import InviteMemberForm from '@/components/workspaces/InviteMemberForm.vue';
import MembersTable from '@/components/workspaces/MembersTable.vue';
import PendingInvitations from '@/components/workspaces/PendingInvitations.vue';
import RoleBadge from '@/components/workspaces/RoleBadge.vue';
import { dashboard } from '@/routes';
import type {
    BoardListItem,
    MembershipRole,
    Workspace,
    WorkspaceAbilities,
    WorkspaceInvitation,
    WorkspaceMember,
} from '@/types';

const props = defineProps<{
    workspace: Workspace;
    role: MembershipRole;
    members: WorkspaceMember[];
    invitations: WorkspaceInvitation[];
    boards: BoardListItem[];
    can: WorkspaceAbilities;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Workspaces', href: dashboard() }],
    },
});

type Tab = 'overview' | 'boards' | 'members' | 'invitations';

const tabs = computed<{ id: Tab; label: string; visible: boolean }[]>(() => [
    { id: 'overview', label: 'Overview', visible: true },
    {
        id: 'boards',
        label: `Boards (${props.boards.length})`,
        visible: true,
    },
    {
        id: 'members',
        label: `Members (${props.members.length})`,
        visible: true,
    },
    {
        id: 'invitations',
        label: `Invitations (${props.invitations.length})`,
        visible: props.can.manageMembers,
    },
]);

const activeTab = ref<Tab>('overview');

const editOpen = ref(false);
const editForm = useForm({
    name: props.workspace.name,
    description: props.workspace.description ?? '',
});

function submitEdit() {
    editForm.put(update.url(props.workspace.id), {
        preserveScroll: true,
        onSuccess: () => {
            editOpen.value = false;
        },
    });
}

function deleteWorkspace() {
    if (
        !window.confirm(
            `Delete "${props.workspace.name}"? This cannot be undone.`,
        )
    ) {
        return;
    }

    router.delete(destroy.url(props.workspace.id));
}
</script>

<template>
    <Head :title="workspace.name" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <h1 class="text-xl font-semibold tracking-tight">
                        {{ workspace.name }}
                    </h1>
                    <RoleBadge :role="role" />
                </div>
                <p class="max-w-prose text-sm text-muted-foreground">
                    {{ workspace.description || 'No description provided.' }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <Button as-child variant="outline">
                    <Link :href="activityIndex.url(workspace.id)">
                        <History class="size-4" />
                        Activity
                    </Link>
                </Button>

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
                                <DialogTitle>Edit workspace</DialogTitle>
                                <DialogDescription>
                                    Update your workspace name and description.
                                </DialogDescription>
                            </DialogHeader>

                            <div class="grid gap-4 py-4">
                                <div class="grid gap-2">
                                    <Label for="edit-name">Name</Label>
                                    <Input
                                        id="edit-name"
                                        v-model="editForm.name"
                                        required
                                    />
                                    <InputError
                                        :message="editForm.errors.name"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="edit-description">
                                        Description
                                    </Label>
                                    <Input
                                        id="edit-description"
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
                    v-if="can.delete"
                    variant="outline"
                    @click="deleteWorkspace"
                >
                    <Trash2 class="size-4 text-destructive" />
                    Delete
                </Button>
            </div>
        </div>

        <div class="flex gap-1 border-b">
            <template v-for="tab in tabs" :key="tab.id">
                <button
                    v-if="tab.visible"
                    type="button"
                    class="border-b-2 px-4 py-2 text-sm font-medium transition-colors"
                    :class="
                        activeTab === tab.id
                            ? 'border-primary text-foreground'
                            : 'border-transparent text-muted-foreground hover:text-foreground'
                    "
                    @click="activeTab = tab.id"
                >
                    {{ tab.label }}
                </button>
            </template>
        </div>

        <div
            v-show="activeTab === 'overview'"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
        >
            <div class="rounded-xl border p-4">
                <div class="text-sm text-muted-foreground">Boards</div>
                <div class="mt-1 text-2xl font-semibold">
                    {{ boards.length }}
                </div>
            </div>
            <div class="rounded-xl border p-4">
                <div class="text-sm text-muted-foreground">Members</div>
                <div class="mt-1 text-2xl font-semibold">
                    {{ members.length }}
                </div>
            </div>
            <div class="rounded-xl border p-4">
                <div class="text-sm text-muted-foreground">
                    Pending invitations
                </div>
                <div class="mt-1 text-2xl font-semibold">
                    {{ invitations.length }}
                </div>
            </div>
            <div class="rounded-xl border p-4">
                <div class="text-sm text-muted-foreground">Your role</div>
                <div class="mt-2">
                    <RoleBadge :role="role" />
                </div>
            </div>
        </div>

        <div v-show="activeTab === 'boards'">
            <BoardsList
                :workspace-id="workspace.id"
                :boards="boards"
                :can-create="can.createBoard"
            />
        </div>

        <div v-show="activeTab === 'members'">
            <MembersTable :members="members" :can-manage="can.manageMembers" />
        </div>

        <div
            v-if="can.manageMembers"
            v-show="activeTab === 'invitations'"
            class="flex flex-col gap-6"
        >
            <InviteMemberForm :workspace-id="workspace.id" />
            <PendingInvitations
                :invitations="invitations"
                :can-manage="can.manageMembers"
            />
        </div>
    </div>
</template>
