<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Send } from '@lucide/vue';
import { store } from '@/actions/App/Http/Controllers/InvitationController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { MembershipRole } from '@/types';

const { workspaceId } = defineProps<{ workspaceId: number }>();

const assignableRoles: MembershipRole[] = ['admin', 'member', 'viewer'];

const form = useForm({
    email: '',
    role: 'member' as MembershipRole,
});

function submit() {
    form.post(store.url(workspaceId), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <form
        class="flex flex-col gap-4 rounded-xl border p-4 sm:flex-row sm:items-start"
        @submit.prevent="submit"
    >
        <div class="grid flex-1 gap-2">
            <Label for="invite-email">Email address</Label>
            <Input
                id="invite-email"
                v-model="form.email"
                type="email"
                placeholder="teammate@example.com"
                required
            />
            <InputError :message="form.errors.email" />
        </div>

        <div class="grid gap-2 sm:w-40">
            <Label for="invite-role">Role</Label>
            <Select v-model="form.role">
                <SelectTrigger id="invite-role" class="w-full">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="role in assignableRoles"
                        :key="role"
                        :value="role"
                    >
                        {{ role.charAt(0).toUpperCase() + role.slice(1) }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="form.errors.role" />
        </div>

        <div class="flex items-end sm:pt-6.5">
            <Button type="submit" :disabled="form.processing">
                <Send class="size-4" />
                Send invite
            </Button>
        </div>
    </form>
</template>
