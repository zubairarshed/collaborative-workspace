<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import type { BadgeVariants } from '@/components/ui/badge';
import type { TaskPriority } from '@/types';

const { priority } = defineProps<{ priority: TaskPriority }>();

const variant = computed<BadgeVariants['variant']>(() => {
    switch (priority) {
        case 'urgent':
            return 'destructive';
        case 'high':
            return 'default';
        case 'medium':
            return 'secondary';
        default:
            return 'outline';
    }
});

const label = computed(
    () => priority.charAt(0).toUpperCase() + priority.slice(1),
);
</script>

<template>
    <Badge :variant="variant" class="text-xs">{{ label }}</Badge>
</template>
