export type DriveSharePermission = 'viewer' | 'editor';

export type DriveOwner = {
    id: number;
    name: string;
    email: string;
};

export type DriveShare = {
    id: number;
    user_id: number;
    user?: DriveOwner | null;
    permission: DriveSharePermission;
    shared_by: number;
    created_at?: string | null;
};

export type DriveShareLink = {
    id: number;
    token: string;
    url: string;
    permission: DriveSharePermission;
    has_password: boolean;
    expires_at?: string | null;
    revoked_at?: string | null;
    is_active: boolean;
    created_at?: string | null;
};

export type DriveFolder = {
    id: number;
    type: 'folder';
    name: string;
    parent_id: number | null;
    owner_id: number;
    owner?: DriveOwner | null;
    created_by: number;
    created_at?: string | null;
    updated_at?: string | null;
    deleted_at?: string | null;
    shares?: DriveShare[];
    share_links?: DriveShareLink[];
};

export type DriveFile = {
    id: number;
    type: 'file';
    name: string;
    folder_id: number | null;
    original_name: string;
    mime: string | null;
    size: number;
    owner_id: number;
    owner?: DriveOwner | null;
    uploaded_by: number;
    previewable: boolean;
    created_at?: string | null;
    updated_at?: string | null;
    deleted_at?: string | null;
    shares?: DriveShare[];
    share_links?: DriveShareLink[];
};

export type DriveStorage = {
    used_bytes: number;
    quota_bytes: number;
    remaining_bytes: number;
    used_percent: number;
    scope: 'department' | 'personal';
};

export type DriveFilters = {
    scope: string;
    folder: number | null;
    q: string;
    type: string;
    owner_id: number | null;
    min_size: number | null;
    max_size: number | null;
    from: string | null;
    to: string | null;
};

export type DriveCan = {
    upload: boolean;
    share: boolean;
    manage: boolean;
    manage_quota: boolean;
};

export type DriveBreadcrumb = {
    id: number;
    name: string;
};
