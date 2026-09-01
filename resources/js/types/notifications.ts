export type AppNotification = {
    id: string;
    type: string;
    data: {
        event?: string;
        demand_id?: number;
        reference?: string;
        status?: string;
        brand_name?: string | null;
        /** @deprecated Use brand_name */
        product_name?: string | null;
        actor_id?: number | null;
        actor_name?: string | null;
        comment?: string | null;
        url?: string;
        message_key?: string;
    };
    read_at: string | null;
    created_at: string | null;
};

export type SharedNotifications = {
    unread_count: number;
    recent: AppNotification[];
};
