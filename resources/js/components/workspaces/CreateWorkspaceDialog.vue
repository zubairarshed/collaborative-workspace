<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import { store } from '@/actions/App/Http/Controllers/WorkspaceController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const open = ref(false);

const form = useForm({
    name: '',
    description: '',
});

function submit() {
    form.post(store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button>
                <Plus class="size-4" />
                Create workspace
            </Button>
        </DialogTrigger>
        <DialogContent>
            <form @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>Create a workspace</DialogTitle>
                    <DialogDescription>
                        Workspaces are where your team organizes projects and
                        collaborates.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="Acme Marketing"
                            required
                            autofocus
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Input
                            id="description"
                            v-model="form.description"
                            placeholder="What is this workspace for? (optional)"
                        />
                        <InputError :message="form.errors.description" />
                    </div>
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="form.processing">
                        Create workspace
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
