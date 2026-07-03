export type MembershipRole = 'owner' | 'admin' | 'member' | 'viewer';

export type WorkspaceListItem = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    role: MembershipRole;
    members_count: number;
    created_at: string;
};

export type PendingWorkspaceInvitation = {
    id: number;
    token: string;
    role: MembershipRole;
    expires_at: string;
    workspace: {
        id: number;
        name: string;
        description: string | null;
    };
};

export type Workspace = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    owner_id: number;
};

export type WorkspaceMember = {
    id: number;
    role: MembershipRole;
    joined_at: string | null;
    is_owner: boolean;
    is_self: boolean;
    user: {
        id: number;
        name: string;
        email: string;
    };
};

export type WorkspaceInvitation = {
    id: number;
    email: string;
    role: MembershipRole;
    expires_at: string;
    created_at: string;
};

export type WorkspaceAbilities = {
    update: boolean;
    delete: boolean;
    manageMembers: boolean;
    createBoard: boolean;
};

export type InvitationPreview = {
    token: string;
    email: string;
    role: MembershipRole;
    workspace_name: string;
    is_pending: boolean;
    email_matches: boolean;
};
