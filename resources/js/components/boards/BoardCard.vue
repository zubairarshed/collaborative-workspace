<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Archive, LayoutDashboard } from '@lucide/vue';
import { show } from '@/actions/App/Http/Controllers/BoardController';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { BoardListItem } from '@/types';

const { workspaceId, board } = defineProps<{
    workspaceId: number;
    board: BoardListItem;
}>();
</script>

<template>
    <Link
        :href="show.url({ workspace: workspaceId, board: board.slug })"
        class="block"
    >
        <Card
            class="h-full transition-colors hover:border-primary/50"
            :class="board.is_archived ? 'opacity-75' : ''"
        >
            <CardHeader>
                <div class="flex items-start justify-between gap-2">
                    <CardTitle class="truncate">{{ board.name }}</CardTitle>
                    <Badge v-if="board.is_archived" variant="secondary">
                        <Archive class="size-3" />
                        Archived
                    </Badge>
                </div>
            </CardHeader>
            <CardContent class="flex flex-col gap-4">
                <p class="line-clamp-2 min-h-10 text-sm text-muted-foreground">
                    {{ board.description || 'No description provided.' }}
                </p>
                <div
                    class="flex items-center gap-1.5 text-sm text-muted-foreground"
                >
                    <LayoutDashboard class="size-4" />
                    Board
                </div>
            </CardContent>
        </Card>
    </Link>
</template>
