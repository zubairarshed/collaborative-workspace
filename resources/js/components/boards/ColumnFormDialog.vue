<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import {
    store,
    update,
} from '@/actions/App/Http/Controllers/BoardColumnController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { BoardColumn } from '@/types';

const open = defineModel<boolean>('open', { required: true });

const props = defineProps<{
    mode: 'create' | 'edit';
    workspaceId: number;
    boardId: number;
    column?: BoardColumn;
}>();

const form = useForm({
    name: '',
    wip_limit: '' as string | number,
});

watch(
    () => [open.value, props.mode, props.column] as const,
    ([isOpen, mode, column]) => {
        if (!isOpen) {
            return;
        }

        if (mode === 'edit' && column) {
            form.defaults({
                name: column.name,
                wip_limit: column.wip_limit ?? '',
            });
        } else {
            form.defaults({ name: '', wip_limit: '' });
        }

        form.reset();
    },
);

function submit() {
    const payload = {
        name: form.name,
        wip_limit: form.wip_limit === '' ? null : Number(form.wip_limit),
    };

    if (props.mode === 'create') {
        form
            .transform(() => payload)
            .post(store.url({ workspace: props.workspaceId, board: props.boardId }), {
                preserveScroll: true,
                onSuccess: () => {
                    open.value = false;
                },
            });

        return;
    }

    if (!props.column) {
        return;
    }

    form
        .transform(() => payload)
        .patch(
            update.url({
                workspace: props.workspaceId,
                board: props.boardId,
                column: props.column.id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    open.value = false;
                },
            },
        );
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <form @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>
                        {{ mode === 'create' ? 'Add column' : 'Edit column' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            mode === 'create'
                                ? 'Add a new workflow column to this board.'
                                : 'Update the column name or WIP limit.'
                        }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="column-name">Name</Label>
                        <Input
                            id="column-name"
                            v-model="form.name"
                            placeholder="In progress"
                            required
                            autofocus
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="column-wip">WIP limit (optional)</Label>
                        <Input
                            id="column-wip"
                            v-model="form.wip_limit"
                            type="number"
                            min="1"
                            max="1000"
                            placeholder="No limit"
                        />
                        <InputError :message="form.errors.wip_limit" />
                    </div>
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="form.processing">
                        {{ mode === 'create' ? 'Add column' : 'Save changes' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
