<?php

namespace App\Actions\Invitations;

use App\Enums\MembershipRole;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InviteMember
{
    /**
     * Number of days an invitation remains valid.
     */
    public const EXPIRY_DAYS = 7;

    /**
     * Create a pending invitation for an email address.
     *
     * Enforces the core invariants ("cannot invite an existing member" and
     * "no duplicate pending invitation") defensively, regardless of caller.
     *
     * @throws ValidationException
     */
    public function handle(Workspace $workspace, User $inviter, string $email, MembershipRole $role): Invitation
    {
        $email = Str::lower(trim($email));

        if ($workspace->members()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'This person is already a member of the workspace.',
            ]);
        }

        if ($workspace->invitations()->pending()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'A pending invitation already exists for this email.',
            ]);
        }

        return $workspace->invitations()->create([
            'invited_by' => $inviter->id,
            'email' => $email,
            'role' => $role,
            'token' => Str::random(40),
            'expires_at' => now()->addDays(self::EXPIRY_DAYS),
        ]);
    }
}
