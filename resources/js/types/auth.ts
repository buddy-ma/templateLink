export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    /** True when the user has the `access_admin` permission (e.g. `admin` role). */
    is_admin?: boolean;
    roles?: string[];
    permissions?: string[];
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
