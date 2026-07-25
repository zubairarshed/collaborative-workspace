export type TaskPriority = 'low' | 'medium' | 'high' | 'urgent';

export type TaskPerson = {
    id: number;
    name: string;
};

export type TaskAbilities = {
    update: boolean;
    move: boolean;
    archive: boolean;
    delete: boolean;
};

export type TaskComment = {
    id: number;
    body: string;
    created_at: string;
    author: TaskPerson | null;
};

export type BoardTask = {
    id: number;
    title: string;
    description: string | null;
    priority: TaskPriority;
    due_at: string | null;
    position: number;
    version: number;
    assignee: TaskPerson | null;
    creator: TaskPerson | null;
    comments: TaskComment[];
    can: TaskAbilities;
};

export type WorkspaceMemberOption = {
    id: number;
    name: string;
};
