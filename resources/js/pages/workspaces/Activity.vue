<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { show as showWorkspace } from '@/actions/App/Http/Controllers/WorkspaceController';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import type { ActivityWorkspace, PaginatedActivities } from '@/types';

defineProps<{
    workspace: ActivityWorkspace;
    activities: PaginatedActivities;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Workspaces', href: dashboard() }],
    },
});

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}
</script>

<template>
    <Head :title="`${workspace.name} — Activity`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex items-center gap-3">
            <Button as-child variant="outline" size="icon-sm">
                <Link :href="showWorkspace.url(workspace.id)">
                    <ArrowLeft class="size-4" />
                    <span class="sr-only">Back to workspace</span>
                </Link>
            </Button>
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Activity</h1>
                <p class="text-sm text-muted-foreground">
                    Everything that has happened in {{ workspace.name }}.
                </p>
            </div>
        </div>

        <div
            v-if="activities.data.length === 0"
            class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            No activity yet.
        </div>

        <ul v-else class="divide-y rounded-xl border">
            <li
                v-for="activity in activities.data"
                :key="activity.id"
                class="px-4 py-3 text-sm"
            >
                <p>{{ activity.message }}</p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ formatDateTime(activity.created_at) }}
                </p>
            </li>
        </ul>

        <div
            v-if="activities.prev_page_url || activities.next_page_url"
            class="flex items-center justify-between"
        >
            <Button
                v-if="activities.prev_page_url"
                as-child
                variant="outline"
                size="sm"
            >
                <Link :href="activities.prev_page_url" preserve-scroll
                    >Previous</Link
                >
            </Button>
            <Button v-else variant="outline" size="sm" disabled
                >Previous</Button
            >

            <span class="text-sm text-muted-foreground">
                Page {{ activities.current_page }} of {{ activities.last_page }}
            </span>

            <Button
                v-if="activities.next_page_url"
                as-child
                variant="outline"
                size="sm"
            >
                <Link :href="activities.next_page_url" preserve-scroll
                    >Next</Link
                >
            </Button>
            <Button v-else variant="outline" size="sm" disabled>Next</Button>
        </div>
    </div>
</template>
