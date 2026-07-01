<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Users } from '@lucide/vue';
import { show } from '@/actions/App/Http/Controllers/WorkspaceController';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import RoleBadge from '@/components/workspaces/RoleBadge.vue';
import type { WorkspaceListItem } from '@/types';

const { workspace } = defineProps<{ workspace: WorkspaceListItem }>();
</script>

<template>
    <Link :href="show.url(workspace.id)" class="block">
        <Card class="h-full transition-colors hover:border-primary/50">
            <CardHeader>
                <div class="flex items-start justify-between gap-2">
                    <CardTitle class="truncate">{{ workspace.name }}</CardTitle>
                    <RoleBadge :role="workspace.role" />
                </div>
            </CardHeader>
            <CardContent class="flex flex-col gap-4">
                <p class="line-clamp-2 min-h-10 text-sm text-muted-foreground">
                    {{ workspace.description || 'No description provided.' }}
                </p>
                <div
                    class="flex items-center gap-1.5 text-sm text-muted-foreground"
                >
                    <Users class="size-4" />
                    {{ workspace.members_count }}
                    {{ workspace.members_count === 1 ? 'member' : 'members' }}
                </div>
            </CardContent>
        </Card>
    </Link>
</template>
