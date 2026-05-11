export interface User {
    id: number;
    username: string;
}

export interface AuthResponse {
    status: 'success' | 'error';
    user?: User;
    token?: string;
    message?: string;
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

export interface ApiResponse<T> {
    status: string;
    data: T;
    message?: string;
}