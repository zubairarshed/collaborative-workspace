<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { LogOut, Trash2 } from '@lucide/vue';
import {
    destroy,
    update,
} from '@/actions/App/Http/Controllers/MembershipController';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import RoleBadge from '@/components/workspaces/RoleBadge.vue';
import type { MembershipRole, WorkspaceMember } from '@/types';

const { members, canManage } = defineProps<{
    members: WorkspaceMember[];
    canManage: boolean;
}>();

const assignableRoles: MembershipRole[] = ['admin', 'member', 'viewer'];

function changeRole(member: WorkspaceMember, role: MembershipRole) {
    if (role === member.role) {
        return;
    }

    router.patch(update.url(member.id), { role }, { preserveScroll: true });
}

function removeMember(member: WorkspaceMember) {
    const message = member.is_self
        ? 'Are you sure you want to leave this workspace?'
        : `Remove ${member.user.name} from this workspace?`;

    if (!window.confirm(message)) {
        return;
    }

    router.delete(destroy.url(member.id), { preserveScroll: true });
}

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <div class="overflow-x-auto rounded-xl border">
        <table class="w-full text-sm">
            <thead class="border-b bg-muted/50 text-left text-muted-foreground">
                <tr>
                    <th class="px-4 py-3 font-medium">Member</th>
                    <th class="px-4 py-3 font-medium">Role</th>
                    <th class="px-4 py-3 font-medium">Joined</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr v-for="member in members" :key="member.id">
                    <td class="px-4 py-3">
                        <div class="font-medium">{{ member.user.name }}</div>
                        <div class="text-muted-foreground">
                            {{ member.user.email }}
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <Select
                            v-if="canManage && !member.is_owner"
                            :model-value="member.role"
                            @update:model-value="
                                (value) =>
                                    changeRole(member, value as MembershipRole)
                            "
                        >
                            <SelectTrigger class="w-32">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="role in assignableRoles"
                                    :key="role"
                                    :value="role"
                                >
                                    {{
                                        role.charAt(0).toUpperCase() +
                                        role.slice(1)
                                    }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <RoleBadge v-else :role="member.role" />
                    </td>
                    <td class="px-4 py-3 text-muted-foreground">
                        {{ formatDate(member.joined_at) }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <Button
                            v-if="member.is_self && !member.is_owner"
                            variant="ghost"
                            size="sm"
                            @click="removeMember(member)"
                        >
                            <LogOut class="size-4" />
                            Leave
                        </Button>
                        <Button
                            v-else-if="canManage && !member.is_owner"
                            variant="ghost"
                            size="icon-sm"
                            @click="removeMember(member)"
                        >
                            <Trash2 class="size-4 text-destructive" />
                            <span class="sr-only">Remove member</span>
                        </Button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
