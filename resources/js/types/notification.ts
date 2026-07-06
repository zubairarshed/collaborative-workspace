export type NotificationItem = {
    id: number;
    message: string;
    read_at: string | null;
    created_at: string;
};

export type PaginatedNotifications = {
    data: NotificationItem[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
};
