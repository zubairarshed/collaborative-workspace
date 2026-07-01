<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import type { BadgeVariants } from '@/components/ui/badge';
import type { MembershipRole } from '@/types';

const { role } = defineProps<{ role: MembershipRole }>();

const variant = computed<BadgeVariants['variant']>(() => {
    switch (role) {
        case 'owner':
            return 'default';
        case 'admin':
            return 'secondary';
        default:
            return 'outline';
    }
});

const label = computed(() => role.charAt(0).toUpperCase() + role.slice(1));
</script>

<template>
    <Badge :variant="variant">{{ label }}</Badge>
</template>
