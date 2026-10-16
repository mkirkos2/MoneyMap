<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    ArrowDownRight,
    ArrowUpRight,
    ChartNoAxesColumnIncreasing,
    CircleAlert,
    CircleCheck,
    Plus,
    Receipt,
    TrendingUp,
    Wallet,
} from '@lucide/vue';
import MoneyAmount from '@/components/MoneyAmount.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { create as createTransaction } from '@/routes/transactions';

interface Props {
    totalIncome: number;
    totalExpenses: number;
    balance: number;
    recentTransactions: Array<{
        id: number;
        description: string;
        amount: number;
        type: 'income' | 'expense';
        transaction_date: string;
        category?: {
            name: string;
            color?: string;
        };
    }>;
    expensesByCategory: Array<{
        category: string;
        amount: number | string;
        color?: string;
    }>;
    financialInsights: {
        periods: {
            current: { key: string; income: string; expenses: string; net: string };
            previous: { key: string; income: string; expenses: string; net: string };
        };
        changes: {
            income: { amount: string; percentage: number | null };
            expenses: { amount: string; percentage: number | null };
            net: { amount: string };
        };
        top_expense_categories: Array<{
            category_id: number | null;
            name: string;
            color: string;
            current_amount: string;
            previous_amount: string;
            increase_amount: string;
            share_percentage: number | null;
            percentage_change: number | null;
        }>;
        increased_expense_categories: Array<{
            category_id: number | null;
            name: string;
            color: string;
            current_amount: string;
            previous_amount: string;
            increase_amount: string;
            share_percentage: number | null;
            percentage_change: number | null;
        }>;
        budget_alerts: Array<{
            budget_id: number;
            category_id: number;
            category_name: string;
            category_color: string;
            limit_amount: string;
            spent_amount: string;
            remaining_amount: string;
            usage_percentage: number | null;
            status: 'approaching' | 'exceeded';
        }>;
    };
}

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard.url(),
            },
        ],
    },
});

const balanceType = computed(() => {
    if (props.balance > 0) return 'income';
    if (props.balance < 0) return 'expense';
    return 'neutral';
});

const categoryTotal = computed(() =>
    (props.expensesByCategory ?? []).reduce((total, item) => total + Number(item.amount), 0),
);

const categoryBarWidth = (amount: number | string): string => {
    if (categoryTotal.value <= 0) return '0%';

    return `${Math.max((Number(amount) / categoryTotal.value) * 100, 1)}%`;
};

const formatDate = (date: string): string =>
    new Intl.DateTimeFormat('el-GR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(date));

const formatMonth = (month: string): string => {
    const [year, monthNumber] = month.split('-').map(Number);

    return new Intl.DateTimeFormat('el-GR', {
        month: 'long',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(new Date(Date.UTC(year, monthNumber - 1, 1)));
};

const formatPercentage = (percentage: number): string =>
    `${new Intl.NumberFormat('el-GR', { maximumFractionDigits: 1 }).format(percentage)}%`;

const amountType = (amount: string): 'income' | 'expense' | 'neutral' => {
    const numericAmount = Number(amount);

    return numericAmount > 0 ? 'income' : numericAmount < 0 ? 'expense' : 'neutral';
};

const expenseChangeType = (amount: string): 'income' | 'expense' =>
    Number(amount) > 0 ? 'expense' : 'income';

const progressWidth = (percentage: number | null): number =>
    Math.min(Math.max(percentage ?? 0, 0), 100);
</script>

<template>
    <Head title="Dashboard" />

    <main class="mx-auto flex min-h-full w-full min-w-0 max-w-[1600px] flex-1 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold tracking-[0.14em] text-finance-accent">PERSONAL FINANCE</p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-finance-text sm:text-[28px]">Overview</h1>
                <p class="mt-1 text-sm text-finance-muted">Your income, expenses, and latest activity.</p>
            </div>

            <Button as-child class="h-10 shrink-0 rounded-md bg-finance-action px-4 font-semibold text-finance-action-text hover:bg-finance-action-hover">
                <Link :href="createTransaction.url()">
                    <Plus class="size-4" />
                    Add transaction
                </Link>
            </Button>
        </header>

        <Card class="min-w-0 gap-0 rounded-lg border-finance-border bg-finance-surface py-0 shadow-none">
            <CardHeader class="flex flex-col gap-2 border-b border-finance-border px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div class="grid gap-1">
                    <CardTitle class="text-sm font-semibold text-finance-text">Monthly overview</CardTitle>
                    <CardDescription class="text-xs text-finance-subtle">
                        {{ formatMonth(financialInsights.periods.current.key) }} compared with {{ formatMonth(financialInsights.periods.previous.key) }}
                    </CardDescription>
                </div>
                <span class="text-[10px] font-medium tracking-wide text-finance-subtle">RECORDED ACTIVITY</span>
            </CardHeader>
            <CardContent class="grid gap-5 px-5 py-5 sm:grid-cols-3 sm:gap-6 sm:px-6">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-finance-muted">Income</p>
                    <MoneyAmount class="mt-2 block text-xl font-semibold" :amount="financialInsights.periods.current.income" type="income" />
                    <div class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                        <span class="text-finance-subtle">vs {{ formatMonth(financialInsights.periods.previous.key) }}:</span>
                        <span :class="Number(financialInsights.changes.income.amount) >= 0 ? 'text-finance-positive' : 'text-finance-negative'">
                            {{ Number(financialInsights.changes.income.amount) > 0 ? '+' : Number(financialInsights.changes.income.amount) < 0 ? '−' : '' }}<MoneyAmount class="inline text-xs" :amount="Math.abs(Number(financialInsights.changes.income.amount))" :type="amountType(financialInsights.changes.income.amount)" />
                        </span>
                        <span class="text-finance-faint">·</span>
                        <span class="text-finance-subtle">{{ financialInsights.changes.income.percentage === null ? 'No prior-month baseline' : formatPercentage(financialInsights.changes.income.percentage) }}</span>
                    </div>
                    <p class="mt-1 text-[11px] text-finance-faint">Previous: <MoneyAmount class="inline text-[11px]" :amount="financialInsights.periods.previous.income" type="neutral" /></p>
                </div>

                <div class="min-w-0 border-t border-finance-border-soft pt-5 sm:border-t-0 sm:border-l sm:pt-0 sm:pl-6">
                    <p class="text-xs font-medium text-finance-muted">Expenses</p>
                    <MoneyAmount class="mt-2 block text-xl font-semibold" :amount="financialInsights.periods.current.expenses" type="expense" />
                    <div class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                        <span class="text-finance-subtle">vs {{ formatMonth(financialInsights.periods.previous.key) }}:</span>
                        <span :class="Number(financialInsights.changes.expenses.amount) <= 0 ? 'text-finance-positive' : 'text-finance-negative'">
                            {{ Number(financialInsights.changes.expenses.amount) > 0 ? '+' : Number(financialInsights.changes.expenses.amount) < 0 ? '−' : '' }}<MoneyAmount class="inline text-xs" :amount="Math.abs(Number(financialInsights.changes.expenses.amount))" :type="expenseChangeType(financialInsights.changes.expenses.amount)" />
                        </span>
                        <span class="text-finance-faint">·</span>
                        <span class="text-finance-subtle">{{ financialInsights.changes.expenses.percentage === null ? 'No prior-month baseline' : formatPercentage(financialInsights.changes.expenses.percentage) }}</span>
                    </div>
                    <p class="mt-1 text-[11px] text-finance-faint">Previous: <MoneyAmount class="inline text-[11px]" :amount="financialInsights.periods.previous.expenses" type="neutral" /></p>
                </div>

                <div class="min-w-0 border-t border-finance-border-soft pt-5 sm:border-t-0 sm:border-l sm:pt-0 sm:pl-6">
                    <p class="text-xs font-medium text-finance-muted">Monthly net result</p>
                    <MoneyAmount class="mt-2 block text-xl font-semibold" :amount="financialInsights.periods.current.net" :type="amountType(financialInsights.periods.current.net)" />
                    <p class="mt-3 text-xs text-finance-subtle">Previous: <MoneyAmount class="inline text-xs" :amount="financialInsights.periods.previous.net" type="neutral" /></p>
                    <p class="mt-1 flex items-center gap-1 text-xs" :class="Number(financialInsights.changes.net.amount) >= 0 ? 'text-finance-positive' : 'text-finance-negative'">
                        <TrendingUp v-if="Number(financialInsights.changes.net.amount) > 0" class="size-3.5" />
                        <TrendingDown v-else-if="Number(financialInsights.changes.net.amount) < 0" class="size-3.5" />
                        <CircleCheck v-else class="size-3.5" />
                        <span>{{ Number(financialInsights.changes.net.amount) > 0 ? '+' : Number(financialInsights.changes.net.amount) < 0 ? '−' : '' }}<MoneyAmount class="inline text-xs" :amount="Math.abs(Number(financialInsights.changes.net.amount))" :type="amountType(financialInsights.changes.net.amount)" /></span>
                        <span class="text-finance-subtle">net change</span>
                    </p>
                    <p class="mt-1 text-[11px] text-finance-faint">Recorded income minus expenses; not a bank balance.</p>
                </div>
            </CardContent>
        </Card>

        <section aria-label="Monthly expense insights" class="grid min-w-0 gap-5 xl:grid-cols-2">
            <Card class="min-w-0 gap-0 rounded-lg border-finance-border bg-finance-surface py-0 shadow-none">
                <CardHeader class="flex flex-row items-center gap-3 border-b border-finance-border px-5 py-4 sm:px-6">
                    <span class="flex size-8 items-center justify-center border border-finance-border bg-finance-tint text-finance-body">
                        <ChartNoAxesColumnIncreasing class="size-4" />
                    </span>
                    <div class="grid gap-1">
                        <CardTitle class="text-sm font-semibold text-finance-text">Top spending categories</CardTitle>
                        <CardDescription class="text-xs text-finance-subtle">{{ formatMonth(financialInsights.periods.current.key) }}</CardDescription>
                    </div>
                </CardHeader>
                <CardContent v-if="financialInsights.top_expense_categories.length > 0" class="space-y-4 px-5 py-5 sm:px-6">
                    <div v-for="category in financialInsights.top_expense_categories" :key="category.category_id ?? 'uncategorized'" class="grid gap-2">
                        <div class="flex min-w-0 items-center justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-2.5">
                                <span class="size-2 shrink-0 rounded-full" :style="{ backgroundColor: category.color }" aria-hidden="true" />
                                <span class="truncate text-xs font-medium text-finance-body">{{ category.name }}</span>
                            </div>
                            <MoneyAmount class="shrink-0 text-xs" :amount="category.current_amount" type="expense" />
                        </div>
                        <div class="h-1 overflow-hidden bg-finance-hover" role="progressbar" :aria-label="`${category.name} share of monthly expenses`" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="category.share_percentage ?? 0">
                            <div class="h-full bg-finance-negative" :style="{ width: `${progressWidth(category.share_percentage)}%` }" />
                        </div>
                        <p class="text-[11px] text-finance-subtle">{{ category.share_percentage === null ? 'Share unavailable' : `${formatPercentage(category.share_percentage)} of monthly expenses` }}</p>
                    </div>
                </CardContent>
                <CardContent v-else class="px-5 py-10 text-center text-xs text-finance-subtle sm:px-6">
                    No expenses recorded in this month yet.
                </CardContent>
            </Card>

            <Card class="min-w-0 gap-0 rounded-lg border-finance-border bg-finance-surface py-0 shadow-none">
                <CardHeader class="flex flex-row items-center gap-3 border-b border-finance-border px-5 py-4 sm:px-6">
                    <span class="flex size-8 items-center justify-center border border-finance-border bg-finance-tint text-finance-body">
                        <TrendingUp class="size-4" />
                    </span>
                    <div class="grid gap-1">
                        <CardTitle class="text-sm font-semibold text-finance-text">Spending changes</CardTitle>
                        <CardDescription class="text-xs text-finance-subtle">Largest increases by amount</CardDescription>
                    </div>
                </CardHeader>
                <CardContent v-if="financialInsights.increased_expense_categories.length > 0" class="divide-y divide-finance-border-soft px-5 sm:px-6">
                    <div v-for="category in financialInsights.increased_expense_categories" :key="category.category_id ?? 'uncategorized'" class="flex min-w-0 items-center justify-between gap-4 py-4">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <span class="size-2 shrink-0 rounded-full" :style="{ backgroundColor: category.color }" aria-hidden="true" />
                            <div class="min-w-0">
                                <p class="truncate text-xs font-medium text-finance-body">{{ category.name }}</p>
                                <p class="mt-1 text-[11px] text-finance-subtle">
                                    {{ formatMonth(financialInsights.periods.previous.key) }}: <MoneyAmount class="inline text-[11px]" :amount="category.previous_amount" type="neutral" />
                                    <span class="px-1 text-finance-faint">→</span>
                                    <MoneyAmount class="inline text-[11px]" :amount="category.current_amount" type="expense" />
                                </p>
                            </div>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="flex items-center justify-end gap-1 text-xs font-semibold text-finance-negative">
                                <TrendingUp class="size-3.5" />
                                <MoneyAmount class="text-xs" :amount="category.increase_amount" type="expense" />
                            </p>
                            <p class="mt-1 text-[11px] text-finance-subtle">{{ category.percentage_change === null ? 'No prior spending' : `+${formatPercentage(category.percentage_change)}` }}</p>
                        </div>
                    </div>
                </CardContent>
                <CardContent v-else class="px-5 py-10 text-center text-xs text-finance-subtle sm:px-6">
                    No categories have higher spending than the previous month.
                </CardContent>
            </Card>
        </section>

        <Card v-if="financialInsights.budget_alerts.length > 0" class="min-w-0 gap-0 rounded-lg border-finance-border bg-finance-surface py-0 shadow-none">
            <CardHeader class="flex flex-row items-center gap-3 border-b border-finance-border px-5 py-4 sm:px-6">
                <span class="flex size-8 items-center justify-center border border-finance-negative-border bg-finance-negative-soft text-finance-negative">
                    <CircleAlert class="size-4" />
                </span>
                <div class="grid gap-1">
                    <CardTitle class="text-sm font-semibold text-finance-text">Budget alerts</CardTitle>
                    <CardDescription class="text-xs text-finance-subtle">Budgets at 80% or more for {{ formatMonth(financialInsights.periods.current.key) }}</CardDescription>
                </div>
            </CardHeader>
            <CardContent class="grid gap-3 p-4 sm:grid-cols-2 sm:p-5 xl:grid-cols-3 xl:p-6">
                <article v-for="budget in financialInsights.budget_alerts" :key="budget.budget_id" class="grid min-w-0 gap-3 border border-finance-border-soft bg-finance-surface-muted p-4">
                    <div class="flex min-w-0 items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <span class="size-2 shrink-0 rounded-full" :style="{ backgroundColor: budget.category_color }" aria-hidden="true" />
                            <p class="truncate text-xs font-semibold text-finance-text">{{ budget.category_name }}</p>
                        </div>
                        <span class="shrink-0 text-[10px] font-semibold uppercase tracking-wide" :class="budget.status === 'exceeded' ? 'text-finance-negative' : 'text-amber-600 dark:text-amber-400'">
                            {{ budget.status === 'exceeded' ? 'Exceeded' : 'Approaching' }}
                        </span>
                    </div>
                    <div class="h-2 overflow-hidden bg-finance-hover" role="progressbar" :aria-label="`${budget.category_name} budget usage`" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="progressWidth(budget.usage_percentage)">
                        <div class="h-full" :class="budget.status === 'exceeded' ? 'bg-finance-negative' : 'bg-amber-500'" :style="{ width: `${progressWidth(budget.usage_percentage)}%` }" />
                    </div>
                    <div class="grid grid-cols-2 gap-x-3 gap-y-2 text-[11px]">
                        <span class="text-finance-subtle">Spent</span><MoneyAmount class="text-right" :amount="budget.spent_amount" type="expense" />
                        <span class="text-finance-subtle">Limit</span><MoneyAmount class="text-right" :amount="budget.limit_amount" type="neutral" />
                        <span class="text-finance-subtle">Remaining</span><MoneyAmount class="text-right" :amount="budget.remaining_amount" :type="amountType(budget.remaining_amount)" />
                    </div>
                    <p class="text-[11px] text-finance-subtle">
                        {{ budget.usage_percentage === null ? 'Usage percentage unavailable for a zero budget limit.' : `${formatPercentage(budget.usage_percentage)} used` }}
                    </p>
                </article>
            </CardContent>
        </Card>

        <section aria-label="Financial summary" class="grid min-w-0 gap-3 sm:grid-cols-2 xl:grid-cols-3 xl:gap-4">
            <Card class="gap-0 rounded-lg border-finance-accent bg-finance-surface py-0 shadow-none">
                <CardHeader class="flex flex-row items-start justify-between gap-3 px-5 pt-5 pb-2">
                    <div class="grid gap-1.5">
                    <CardDescription class="text-xs font-medium text-finance-muted">Net recorded to date</CardDescription>
                        <CardTitle class="text-[26px] tracking-tight text-finance-text sm:text-[28px]">
                            <MoneyAmount :amount="balance" :type="balanceType" />
                        </CardTitle>
                    </div>
                    <span class="flex size-9 items-center justify-center border border-finance-accent bg-finance-accent-soft text-finance-accent">
                        <Wallet class="size-[18px]" />
                    </span>
                </CardHeader>
                <CardContent class="px-5 pb-5">
                    <p class="text-xs text-finance-subtle">All recorded income minus expenses; not a bank balance</p>
                </CardContent>
            </Card>

            <Card class="gap-0 rounded-lg border-finance-border bg-finance-surface py-0 shadow-none">
                <CardHeader class="flex flex-row items-start justify-between gap-3 px-5 pt-5 pb-2">
                    <div class="grid gap-1.5">
                    <CardDescription class="text-xs font-medium text-finance-muted">Income to date</CardDescription>
                        <CardTitle class="text-[26px] tracking-tight text-finance-text sm:text-[28px]">
                            <MoneyAmount :amount="totalIncome" type="income" />
                        </CardTitle>
                    </div>
                    <span class="flex size-9 items-center justify-center border border-finance-accent bg-finance-accent-soft text-finance-accent">
                        <ArrowDownRight class="size-[18px]" />
                    </span>
                </CardHeader>
                <CardContent class="px-5 pb-5">
                    <p class="text-xs text-finance-subtle">All recorded income</p>
                </CardContent>
            </Card>

            <Card class="gap-0 rounded-lg border-finance-border bg-finance-surface py-0 shadow-none sm:col-span-2 xl:col-span-1">
                <CardHeader class="flex flex-row items-start justify-between gap-3 px-5 pt-5 pb-2">
                    <div class="grid gap-1.5">
                    <CardDescription class="text-xs font-medium text-finance-muted">Expenses to date</CardDescription>
                        <CardTitle class="text-[26px] tracking-tight text-finance-text sm:text-[28px]">
                            <MoneyAmount :amount="Math.abs(totalExpenses)" type="expense" />
                        </CardTitle>
                    </div>
                    <span class="flex size-9 items-center justify-center border border-finance-negative-border bg-finance-negative-soft text-finance-negative">
                        <ArrowUpRight class="size-[18px]" />
                    </span>
                </CardHeader>
                <CardContent class="px-5 pb-5">
                    <p class="text-xs text-finance-subtle">All recorded expenses</p>
                </CardContent>
            </Card>
        </section>

        <section aria-label="Recent activity and spending" class="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,1.35fr)_minmax(300px,0.75fr)]">
            <Card class="min-w-0 gap-0 rounded-lg border-finance-border bg-finance-surface py-0 shadow-none">
                <CardHeader class="flex flex-row items-center justify-between gap-4 border-b border-finance-border px-5 py-4 sm:px-6">
                    <div class="grid gap-1">
                        <CardTitle class="text-sm font-semibold text-finance-text">Recent transactions</CardTitle>
                        <CardDescription class="text-xs text-finance-subtle">Your latest recorded activity</CardDescription>
                    </div>
                    <Link :href="createTransaction.url()" class="shrink-0 text-xs font-medium text-finance-accent transition-colors hover:text-finance-accent">
                        <span class="sm:hidden">Add new</span>
                        <span class="hidden sm:inline">Add transaction</span>
                    </Link>
                </CardHeader>

                <div v-if="(recentTransactions ?? []).length > 0" class="min-w-0">
                    <div class="hidden grid-cols-[minmax(0,1.4fr)_minmax(100px,0.75fr)_minmax(110px,0.8fr)_auto] gap-3 px-6 py-3 text-[10px] font-semibold tracking-wide text-finance-subtle md:grid">
                        <span>DESCRIPTION</span>
                        <span>CATEGORY</span>
                        <span>DATE</span>
                        <span class="text-right">AMOUNT</span>
                    </div>
                    <div
                        v-for="transaction in (recentTransactions ?? [])"
                        :key="transaction.id"
                        class="grid min-w-0 grid-cols-[minmax(0,1fr)_auto] items-center gap-x-3 gap-y-2 border-t border-finance-border-soft px-5 py-4 first:border-t-0 sm:px-6 md:grid-cols-[minmax(0,1.4fr)_minmax(100px,0.75fr)_minmax(110px,0.8fr)_auto] md:gap-3"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <span
                                class="flex size-8 shrink-0 items-center justify-center border"
                                :class="transaction.type === 'income'
                                    ? 'border-finance-accent bg-finance-accent-soft text-finance-accent'
                                    : 'border-finance-border bg-finance-tint text-finance-muted'"
                            >
                                <ArrowDownRight v-if="transaction.type === 'income'" class="size-4" />
                                <ArrowUpRight v-else class="size-4" />
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-finance-text">{{ transaction.description || 'Transaction' }}</p>
                                <p class="mt-1 text-[11px] text-finance-subtle md:hidden">
                                    {{ transaction.category?.name || 'Uncategorized' }}
                                    <span class="px-1 text-finance-faint">·</span>
                                    {{ formatDate(transaction.transaction_date) }}
                                </p>
                            </div>
                        </div>
                        <span class="hidden truncate text-xs text-finance-muted md:block">
                            {{ transaction.category?.name || 'Uncategorized' }}
                        </span>
                        <span class="hidden text-xs text-finance-subtle md:block">
                            {{ formatDate(transaction.transaction_date) }}
                        </span>
                        <MoneyAmount
                            class="text-right text-sm font-semibold"
                            :amount="transaction.amount"
                            :type="transaction.type"
                        />
                    </div>
                </div>

                <CardContent v-else class="flex flex-col items-center px-5 py-10 text-center sm:py-12">
                    <span class="flex size-11 items-center justify-center border border-finance-border bg-finance-tint text-finance-muted">
                        <Receipt class="size-5" />
                    </span>
                    <p class="mt-4 text-sm font-medium text-finance-text">No transactions yet</p>
                    <p class="mt-1 max-w-xs text-xs leading-5 text-finance-subtle">Add your first transaction to see your activity here.</p>
                    <Button as-child class="mt-5 h-9 rounded-md bg-finance-action px-3.5 text-xs font-semibold text-finance-action-text hover:bg-finance-action-hover">
                        <Link :href="createTransaction.url()">
                            <Plus class="size-3.5" />
                            Add transaction
                        </Link>
                    </Button>
                </CardContent>
            </Card>

            <Card class="min-w-0 gap-0 rounded-lg border-finance-border bg-finance-surface py-0 shadow-none">
                <CardHeader class="flex flex-row items-center gap-3 border-b border-finance-border px-5 py-4 sm:px-6">
                    <span class="flex size-8 items-center justify-center border border-finance-border bg-finance-tint text-finance-body">
                        <ChartNoAxesColumnIncreasing class="size-4" />
                    </span>
                    <div class="grid gap-1">
                        <CardTitle class="text-sm font-semibold text-finance-text">Expense breakdown</CardTitle>
                    <CardDescription class="text-xs text-finance-subtle">All recorded expenses by category</CardDescription>
                    </div>
                </CardHeader>

                <CardContent v-if="(expensesByCategory ?? []).length > 0" class="space-y-5 px-5 py-5 sm:px-6">
                    <div v-for="item in (expensesByCategory ?? [])" :key="item.category" class="grid gap-2.5">
                        <div class="flex min-w-0 items-center justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-2.5">
                                <span
                                    class="size-2 shrink-0 rounded-full"
                                    :style="{ backgroundColor: item.color || '#64748b' }"
                                    aria-hidden="true"
                                />
                                <span class="truncate text-xs font-medium text-finance-body">{{ item.category }}</span>
                            </div>
                            <MoneyAmount class="shrink-0 text-xs" :amount="item.amount" type="expense" />
                        </div>
                        <div class="h-1 overflow-hidden bg-finance-hover">
                            <div
                                class="h-full transition-[width] duration-300"
                                :style="{
                                    width: categoryBarWidth(item.amount),
                                    backgroundColor: item.color || '#64748b',
                                }"
                            />
                        </div>
                    </div>
                </CardContent>

                <CardContent v-else class="flex flex-col items-center px-5 py-10 text-center sm:py-12">
                    <span class="flex size-11 items-center justify-center border border-finance-border bg-finance-tint text-finance-muted">
                        <ChartNoAxesColumnIncreasing class="size-5" />
                    </span>
                    <p class="mt-4 text-sm font-medium text-finance-text">No expense data yet</p>
                    <p class="mt-1 max-w-xs text-xs leading-5 text-finance-subtle">Expense categories will appear here when you add transactions.</p>
                </CardContent>
            </Card>
        </section>
    </main>
</template>
