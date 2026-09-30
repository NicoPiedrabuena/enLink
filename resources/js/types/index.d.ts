export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    role: 'user' | 'admin';
    status: 'active' | 'suspended';
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
        publicUrl: string | null;
    };
    flash: {
        toast?: { type?: 'success' | 'error' | 'warning' | 'info'; message?: string };
    };
};
