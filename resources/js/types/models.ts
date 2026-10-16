export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    created_at: string;
    updated_at: string;
}

export interface Category {
    id: number;
    user_id: number;
    name: string;
    type: 'income' | 'expense';
    color?: string;
    icon?: string;
    transactions_count?: number;
    created_at: string;
    updated_at: string;
    deleted_at?: string;
}

export interface Transaction {
    id: number;
    user_id: number;
    category_id?: number;
    type: 'income' | 'expense';
    amount: number;
    transaction_date: string;
    description: string;
    notes?: string;
    created_at: string;
    updated_at: string;
    deleted_at?: string;
    category?: Category;
}

export interface Budget {
    id: number;
    user_id: number;
    category_id: number;
    month: string;
    amount: number | string;
    spent_amount: number;
    remaining_amount: number;
    usage_percentage: number;
    created_at: string;
    updated_at: string;
    category?: Category | null;
}

export interface SavingsGoal {
    id: number;
    name: string;
    target_amount: string;
    target_date: string | null;
    description: string | null;
    current_amount: string;
    remaining_amount: string;
    progress_percentage: number;
    is_complete: boolean;
    is_overdue: boolean;
    months_remaining: number | null;
    monthly_required_amount: string | null;
    created_at: string;
    updated_at: string;
}

export interface SavingsGoalEntry {
    id: number;
    savings_goal_id: number;
    type: 'contribution' | 'withdrawal';
    amount: string;
    entry_date: string;
    note: string | null;
    created_at: string;
    updated_at: string;
}

export interface PaginationLinks {
    first: string;
    last: string;
    prev?: string;
    next?: string;
}

export interface PaginationMeta {
    current_page: number;
    from: number;
    last_page: number;
    links: Array<{
        url?: string;
        label: string;
        active: boolean;
    }>;
    path: string;
    per_page: number;
    to: number;
    total: number;
}

export interface PaginatedResponse<T> {
    data: T[];
    links: PaginationLinks;
    meta: PaginationMeta;
}
