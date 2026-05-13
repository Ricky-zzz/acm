export interface User {
    id: number;
    username: string;
}

export interface AuthResponse {
    token: string;
    user: User;
}

export interface City {
    id?: number; 
    name: string;
    councilor_limit: number;
    created_at?: string;
}

export interface Position {
    id?: number;
    city_id: number;
    title: string;
    max_votes: number;
    city_name?: string;
    created_at?: string;
}

export interface Party {
    id?: number;
    name: string;
    alias: string;
    created_at?: string;
}

export interface ApiErrorResponse {
    message: string;
    errors?: Record<string, string>;
}