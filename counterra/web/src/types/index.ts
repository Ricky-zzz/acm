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

export interface Candidate {
    id?: number;
    name: string;
    position_id: number;
    party_id: number;
    position_title?: string;
    city_name?: string;
    city_id?: number;
    party_name?: string;
    party_alias?: string;
}

export interface Ballot {
    id?: number;
    city_id: number;
    ballot_number: string;
    status: 'unused' | 'used';
    city_name?: string;
}

export interface BallotSummary {
    city_id: number;
    city_name: string;
    total: number;
    unused: number;
    used: number;
}

export interface ResultImportItem {
    ballot_number: string;
    choices: number[];
}

export interface ResultImportPayload {
    city_id: number;
    results: ResultImportItem[];
}

export interface ResultImportResponse {
    status: 'success' | 'error';
    processed: number;
    errors: string[];
}

export interface TallyRow {
    candidate_id: number;
    candidate_name: string;
    position_title: string;
    party_alias: string;
    vote_count: number;
}

export interface ApiErrorResponse {
    message: string;
    errors?: Record<string, string>;
}