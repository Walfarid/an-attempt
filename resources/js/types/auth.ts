// Matches exactly what HandleInertiaRequests shares — nothing more is serialized.
export type User = {
    name: string;
    email: string;
    avatar?: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};
