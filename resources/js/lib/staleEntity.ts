import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

/**
 * Optimistic-concurrency conflict handling (ADR-004).
 *
 * Board mutations are submitted through the Inertia router with the entity's
 * current `version`. When another client changed the entity first, the server
 * responds with a plain JSON 409 (see bootstrap/app.php), which Inertia
 * surfaces as an `httpException` event. We intercept it, discard the
 * optimistic UI state by partially reloading the board props (ColumnsBoard
 * re-syncs its local drag state from props), and explain via a toast.
 */
export function initializeStaleEntityHandling(): void {
    router.on('httpException', (event) => {
        if (event.detail.response.status !== 409) {
            return;
        }

        // Prevent Inertia's default modal for non-Inertia responses.
        event.preventDefault();

        toast.error('This item was updated by someone else. Board refreshed.');

        router.reload({ only: ['board', 'columns'] });
    });
}
