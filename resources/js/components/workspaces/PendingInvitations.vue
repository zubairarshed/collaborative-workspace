<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { MailX } from '@lucide/vue';
import { destroy } from '@/actions/App/Http/Controllers/InvitationController';
import { Button } from '@/components/ui/button';
import RoleBadge from '@/components/workspaces/RoleBadge.vue';
import type { WorkspaceInvitation } from '@/types';

const { invitations, canManage } = defineProps<{
    invitations: WorkspaceInvitation[];
    canManage: boolean;
}>();

function cancel(invitation: WorkspaceInvitation) {
    if (!window.confirm(`Cancel the invitation for ${invitation.email}?`)) {
        return;
    }

    router.delete(destroy.url(invitation.id), { preserveScroll: true });
}

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <div
        v-if="invitations.length === 0"
        class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground"
    >
        No pending invitations.
    </div>

    <ul v-else class="divide-y rounded-xl border">
        <li
            v-for="invitation in invitations"
            :key="invitation.id"
            class="flex items-center justify-between gap-4 px-4 py-3"
        >
            <div class="min-w-0">
                <div class="truncate font-medium">{{ invitation.email }}</div>
                <div class="text-sm text-muted-foreground">
                    Expires {{ formatDate(invitation.expires_at) }}
                </div>
            </div>
            <div class="flex items-center gap-3">
                <RoleBadge :role="invitation.role" />
                <Button
                    v-if="canManage"
                    variant="ghost"
                    size="icon-sm"
                    @click="cancel(invitation)"
                >
                    <MailX class="size-4 text-destructive" />
                    <span class="sr-only">Cancel invitation</span>
                </Button>
            </div>
        </li>
    </ul>
</template>
