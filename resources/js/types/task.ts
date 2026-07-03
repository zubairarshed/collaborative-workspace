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

export type BoardTask = {
    id: number;
    title: string;
    description: string | null;
    priority: TaskPriority;
    due_at: string | null;
    position: number;
    assignee: TaskPerson | null;
    creator: TaskPerson | null;
    can: TaskAbilities;
};

export type WorkspaceMemberOption = {
    id: number;
    name: string;
};
