<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { CheckCircle2, MailWarning } from '@lucide/vue';
import { accept } from '@/actions/App/Http/Controllers/InvitationController';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import RoleBadge from '@/components/workspaces/RoleBadge.vue';
import { dashboard } from '@/routes';
import type { InvitationPreview } from '@/types';

const { invitation } = defineProps<{ invitation: InvitationPreview }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Invitation', href: dashboard() }],
    },
});

const canAccept = invitation.is_pending && invitation.email_matches;

const form = useForm({});

function acceptInvitation() {
    form.post(accept.url(invitation.token));
}
</script>

<template>
    <Head title="Accept invitation" />

    <div class="flex flex-1 items-center justify-center p-4">
        <Card class="w-full max-w-md">
            <CardHeader>
                <div class="mb-2 flex justify-center">
                    <div
                        class="rounded-full p-3"
                        :class="canAccept ? 'bg-primary/10' : 'bg-muted'"
                    >
                        <CheckCircle2
                            v-if="canAccept"
                            class="size-6 text-primary"
                        />
                        <MailWarning
                            v-else
                            class="size-6 text-muted-foreground"
                        />
                    </div>
                </div>
                <CardTitle class="text-center">
                    <template v-if="canAccept"> You've been invited </template>
                    <template v-else>Invitation unavailable</template>
                </CardTitle>
                <CardDescription class="text-center">
                    <template v-if="canAccept">
                        Join <strong>{{ invitation.workspace_name }}</strong> to
                        start collaborating.
                    </template>
                    <template v-else-if="!invitation.is_pending">
                        This invitation has expired or has already been used.
                    </template>
                    <template v-else>
                        This invitation was sent to
                        <strong>{{ invitation.email }}</strong
                        >. Sign in with that email to accept it.
                    </template>
                </CardDescription>
            </CardHeader>

            <CardContent
                v-if="canAccept"
                class="flex items-center justify-center gap-2 text-sm text-muted-foreground"
            >
                <span>Role:</span>
                <RoleBadge :role="invitation.role" />
            </CardContent>

            <CardFooter class="flex justify-center">
                <Button
                    v-if="canAccept"
                    :disabled="form.processing"
                    @click="acceptInvitation"
                >
                    Accept invitation
                </Button>
                <Button v-else variant="outline" as-child>
                    <a :href="dashboard().url">Go to dashboard</a>
                </Button>
            </CardFooter>
        </Card>
    </div>
</template>
