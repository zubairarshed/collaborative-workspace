<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { LayoutGrid, Mail } from '@lucide/vue';
import { show as showInvitation } from '@/actions/App/Http/Controllers/InvitationController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import CreateWorkspaceDialog from '@/components/workspaces/CreateWorkspaceDialog.vue';
import RoleBadge from '@/components/workspaces/RoleBadge.vue';
import WorkspaceCard from '@/components/workspaces/WorkspaceCard.vue';
import { dashboard } from '@/routes';
import type { PendingWorkspaceInvitation, WorkspaceListItem } from '@/types';

defineProps<{
    workspaces: WorkspaceListItem[];
    pendingInvitations: PendingWorkspaceInvitation[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Workspaces', href: dashboard() }],
    },
});

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <Head title="Workspaces" />

    <div class="flex h-full flex-1 flex-col gap-8 p-4">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Workspaces</h1>
                <p class="text-sm text-muted-foreground">
                    Collaborate with your team across shared workspaces.
                </p>
            </div>
            <CreateWorkspaceDialog />
        </div>

        <section v-if="pendingInvitations.length > 0" class="space-y-4">
            <div class="flex items-center gap-2">
                <h2 class="text-base font-semibold tracking-tight">
                    Pending invitations
                </h2>
                <Badge variant="secondary">{{
                    pendingInvitations.length
                }}</Badge>
            </div>

            <div
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <Card
                    v-for="invitation in pendingInvitations"
                    :key="invitation.id"
                    class="border-primary/30 bg-primary/[0.03]"
                >
                    <CardHeader>
                        <div class="flex items-start justify-between gap-3">
                            <CardTitle class="truncate">
                                {{ invitation.workspace.name }}
                            </CardTitle>
                            <RoleBadge :role="invitation.role" />
                        </div>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-4">
                        <p
                            class="line-clamp-2 min-h-10 text-sm text-muted-foreground"
                        >
                            {{
                                invitation.workspace.description ||
                                'You have been invited to collaborate.'
                            }}
                        </p>
                        <div class="flex items-center justify-between gap-3">
                            <div
                                class="flex items-center gap-1.5 text-sm text-muted-foreground"
                            >
                                <Mail class="size-4" />
                                Expires {{ formatDate(invitation.expires_at) }}
                            </div>
                            <Button size="sm" as-child>
                                <Link
                                    :href="showInvitation.url(invitation.token)"
                                >
                                    Review invite
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </section>

        <section class="flex flex-1 flex-col gap-4">
            <div class="flex items-center gap-2">
                <h2 class="text-base font-semibold tracking-tight">
                    Your workspaces
                </h2>
                <Badge v-if="workspaces.length > 0" variant="secondary">
                    {{ workspaces.length }}
                </Badge>
            </div>

            <div
                v-if="workspaces.length === 0"
                class="flex flex-1 flex-col items-center justify-center gap-4 rounded-xl border border-dashed p-12 text-center"
            >
                <div class="rounded-full bg-muted p-3">
                    <LayoutGrid class="size-6 text-muted-foreground" />
                </div>
                <div class="space-y-1">
                    <h3 class="text-lg font-medium">No workspaces yet</h3>
                    <p class="max-w-sm text-sm text-muted-foreground">
                        Create your first workspace to start organizing projects
                        and inviting teammates.
                    </p>
                </div>
                <CreateWorkspaceDialog />
            </div>

            <div
                v-else
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <WorkspaceCard
                    v-for="workspace in workspaces"
                    :key="workspace.id"
                    :workspace="workspace"
                />
            </div>
        </section>
    </div>
</template>
