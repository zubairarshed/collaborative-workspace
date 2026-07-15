<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { store as storeComment } from '@/actions/App/Http/Controllers/CommentController';
import { store, update } from '@/actions/App/Http/Controllers/TaskController';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { BoardTask, TaskPriority, WorkspaceMemberOption } from '@/types';

const open = defineModel<boolean>('open', { required: true });

const props = defineProps<{
    mode: 'create' | 'edit';
    workspaceId: number;
    boardId: number;
    columnId: number;
    members: WorkspaceMemberOption[];
    task?: BoardTask;
}>();

const priorities: TaskPriority[] = ['low', 'medium', 'high', 'urgent'];

const form = useForm({
    title: '',
    description: '',
    priority: 'medium' as TaskPriority,
    due_at: '',
    assignee_id: 'none' as string | number,
});

watch(
    () => [open.value, props.mode, props.task] as const,
    ([isOpen, mode, task]) => {
        if (!isOpen) {
            return;
        }

        if (mode === 'edit' && task) {
            form.defaults({
                title: task.title,
                description: task.description ?? '',
                priority: task.priority,
                due_at: task.due_at ? task.due_at.slice(0, 10) : '',
                assignee_id: task.assignee?.id ?? 'none',
            });
        } else {
            form.defaults({
                title: '',
                description: '',
                priority: 'medium',
                due_at: '',
                assignee_id: 'none',
            });
        }

        form.reset();
    },
);

const commentForm = useForm({
    body: '',
});

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString(undefined, {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

function submitComment() {
    if (!props.task) {
        return;
    }

    commentForm.post(
        storeComment.url({
            workspace: props.workspaceId,
            board: props.boardId,
            task: props.task.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                commentForm.reset();
            },
        },
    );
}

function submit() {
    const payload = {
        title: form.title,
        description: form.description || null,
        priority: form.priority,
        due_at: form.due_at || null,
        assignee_id:
            form.assignee_id === 'none' ? null : Number(form.assignee_id),
    };

    if (props.mode === 'create') {
        form.transform(() => payload).post(
            store.url({
                workspace: props.workspaceId,
                board: props.boardId,
                column: props.columnId,
            }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    open.value = false;
                },
            },
        );

        return;
    }

    if (!props.task) {
        return;
    }

    const version = props.task.version;

    form.transform(() => ({ ...payload, version })).patch(
        update.url({
            workspace: props.workspaceId,
            board: props.boardId,
            task: props.task.id,
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
                        {{ mode === 'create' ? 'Add task' : 'Edit task' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            mode === 'create'
                                ? 'Create a new task in this column.'
                                : 'Update task details.'
                        }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="task-title">Title</Label>
                        <Input
                            id="task-title"
                            v-model="form.title"
                            placeholder="Write release notes"
                            required
                            autofocus
                        />
                        <InputError :message="form.errors.title" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="task-description">Description</Label>
                        <Input
                            id="task-description"
                            v-model="form.description"
                            placeholder="Optional details"
                        />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="task-priority">Priority</Label>
                            <Select v-model="form.priority">
                                <SelectTrigger id="task-priority">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="priority in priorities"
                                        :key="priority"
                                        :value="priority"
                                    >
                                        {{
                                            priority.charAt(0).toUpperCase() +
                                            priority.slice(1)
                                        }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.priority" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="task-due">Due date</Label>
                            <Input
                                id="task-due"
                                v-model="form.due_at"
                                type="date"
                            />
                            <InputError :message="form.errors.due_at" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="task-assignee">Assignee</Label>
                        <Select v-model="form.assignee_id">
                            <SelectTrigger id="task-assignee">
                                <SelectValue placeholder="Unassigned" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">Unassigned</SelectItem>
                                <SelectItem
                                    v-for="member in members"
                                    :key="member.id"
                                    :value="member.id"
                                >
                                    {{ member.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.assignee_id" />
                    </div>
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="form.processing">
                        {{ mode === 'create' ? 'Add task' : 'Save changes' }}
                    </Button>
                </DialogFooter>
            </form>

            <div
                v-if="mode === 'edit' && task"
                class="flex flex-col gap-3 border-t pt-4"
            >
                <h3 class="text-sm font-medium">Comments</h3>

                <ul v-if="task.comments.length > 0" class="flex flex-col gap-3">
                    <li
                        v-for="comment in task.comments"
                        :key="comment.id"
                        class="text-sm"
                    >
                        <div class="flex items-baseline gap-2">
                            <span class="font-medium">
                                {{ comment.author?.name ?? 'Former member' }}
                            </span>
                            <span class="text-xs text-muted-foreground">
                                {{ formatDateTime(comment.created_at) }}
                            </span>
                        </div>
                        <p class="text-muted-foreground">{{ comment.body }}</p>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">
                    No comments yet.
                </p>

                <form
                    class="flex flex-col gap-2"
                    @submit.prevent="submitComment"
                >
                    <Input
                        v-model="commentForm.body"
                        placeholder="Write a comment"
                    />
                    <InputError :message="commentForm.errors.body" />
                    <Button
                        type="submit"
                        variant="outline"
                        size="sm"
                        class="self-start"
                        :disabled="commentForm.processing"
                    >
                        Add comment
                    </Button>
                </form>
            </div>
        </DialogContent>
    </Dialog>
</template>
