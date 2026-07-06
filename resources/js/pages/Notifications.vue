<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    markAllRead,
    markRead,
} from '@/actions/App/Http/Controllers/NotificationController';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import type { PaginatedNotifications } from '@/types';

const props = defineProps<{
    notifications: PaginatedNotifications;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Notifications', href: dashboard() }],
    },
});

const hasUnread = () => props.notifications.data.some((item) => item.read_at === null);

function markOneRead(id: number) {
    router.patch(markRead.url(id), {}, { preserveScroll: true });
}

function markAll() {
    router.patch(markAllRead.url(), {}, { preserveScroll: true });
}

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
    <Head title="Notifications" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-xl font-semibold tracking-tight">Notifications</h1>
            <Button v-if="hasUnread()" variant="outline" size="sm" @click="markAll">
                Mark all read
            </Button>
        </div>

        <div
            v-if="notifications.data.length === 0"
            class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            No notifications yet.
        </div>

        <ul v-else class="divide-y rounded-xl border">
            <li
                v-for="notification in notifications.data"
                :key="notification.id"
                class="flex items-start justify-between gap-4 px-4 py-3 text-sm"
                :class="notification.read_at === null ? 'bg-muted/40' : ''"
            >
                <div>
                    <p>{{ notification.message }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ formatDateTime(notification.created_at) }}
                    </p>
                </div>
                <Button
                    v-if="notification.read_at === null"
                    variant="ghost"
                    size="sm"
                    @click="markOneRead(notification.id)"
                >
                    Mark read
                </Button>
            </li>
        </ul>

        <div
            v-if="notifications.prev_page_url || notifications.next_page_url"
            class="flex items-center justify-between"
        >
            <Button v-if="notifications.prev_page_url" as-child variant="outline" size="sm">
                <Link :href="notifications.prev_page_url" preserve-scroll>Previous</Link>
            </Button>
            <Button v-else variant="outline" size="sm" disabled>Previous</Button>

            <span class="text-sm text-muted-foreground">
                Page {{ notifications.current_page }} of {{ notifications.last_page }}
            </span>

            <Button v-if="notifications.next_page_url" as-child variant="outline" size="sm">
                <Link :href="notifications.next_page_url" preserve-scroll>Next</Link>
            </Button>
            <Button v-else variant="outline" size="sm" disabled>Next</Button>
        </div>
    </div>
</template>
