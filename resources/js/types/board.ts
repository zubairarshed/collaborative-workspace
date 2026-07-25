import type { BoardTask } from './task';

export type BoardListItem = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_archived: boolean;
    position: number;
};

export type BoardWorkspace = {
    id: number;
    name: string;
    slug: string;
};

export type BoardCreator = {
    id: number;
    name: string;
};

export type BoardDetail = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_archived: boolean;
    position: number;
    version: number;
    created_at: string;
    creator: BoardCreator | null;
};

export type BoardColumn = {
    id: number;
    name: string;
    key: string | null;
    position: number;
    wip_limit: number | null;
    version: number;
    can?: {
        createTask: boolean;
    };
    tasks?: BoardTask[];
};

export type BoardAbilities = {
    update: boolean;
    archive: boolean;
    delete: boolean;
    createColumn: boolean;
    reorderColumns: boolean;
};
