export type User = {
    id: number;
    name: string;
    email: string;
    role?: 'admin' | 'staff' | 'customer';
    contact?: string | null;
    dob?: string | null;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    orders_count?: number;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    // On public storefront pages there may be no authenticated user; guard with
    // optional chaining when reading `user` outside authenticated layouts.
    user: User;
    canAccessAdminPanel?: boolean;
    isAdmin?: boolean;
};

/* @chisel-passkeys */
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
