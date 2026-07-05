export type ActivityItem = {
    id: number;
    message: string;
    causer: {
        id: number;
        name: string;
    } | null;
    created_at: string;
};

export type ActivityWorkspace = {
    id: number;
    name: string;
    slug: string;
};

export type PaginatedActivities = {
    data: ActivityItem[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
};
