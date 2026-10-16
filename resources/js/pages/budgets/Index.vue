<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeft,
    ArrowRight,
    CalendarDays,
    Check,
    Edit,
    FolderOpen,
    LoaderCircle,
    Plus,
    Tag,
    Trash2,
    WalletCards,
} from '@lucide/vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create as createBudget, destroy as destroyBudget, edit, index as budgetsIndex } from '@/routes/budgets';
import { index as categoriesIndex } from '@/routes/categories';
import type { Budget, Category } from '@/types';

interface BudgetPagination {
    data: Budget[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface Props {
    budgets: BudgetPagination;
    categories: Category[];
    filters: {
        month?: string | null;
    };
}

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Budgets',
                href: budgetsIndex.url(),
            },
        ],
    },
});

const filterForm = useForm({ month: props.filters.month ?? '' });
const hasActiveMonthFilter = computed(() => Boolean(props.filters.month));
const deletingBudgetId = ref<number | null>(null);

const applyFilters = (): void => {
    router.get(budgetsIndex.url(), { month: filterForm.month }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = (): void => {
    filterForm.month = '';
    router.get(budgetsIndex.url(), {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatMonth = (month: string): string => {
    const [year, monthNumber] = month.split('-').map(Number);

    return new Intl.DateTimeFormat('el-GR', {
        month: 'long',
        year: 'numeric',
    }).format(new Date(year, monthNumber - 1, 1));
};

const formatCurrency = (amount: number | string): string => {
    const numericAmount = typeof amount === 'string' ? Number(amount) : amount;

    return new Intl.NumberFormat('el-GR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number.isFinite(numericAmount) ? numericAmount : 0);
};

const getUsageState = (budget: Budget): 'on-track' | 'near-limit' | 'over-budget' => {
    if (budget.usage_percentage > 100) {
        return 'over-budget';
    }

    return budget.usage_percentage >= 80 ? 'near-limit' : 'on-track';
};

const progressWidth = (budget: Budget): number => Math.min(100, Math.max(0, budget.usage_percentage));

const handleDelete = (budgetId: number): void => {
    deletingBudgetId.value = budgetId;
    router.delete(destroyBudget.url({ budget: budgetId }), {
        onFinish: () => {
            deletingBudgetId.value = null;
        },
    });
};
</script>

<template>
    <Head title="Budgets" />

    <main class="mx-auto flex min-h-full w-full min-w-0 max-w-[1600px] flex-1 flex-col gap-5 px-4 py-6 sm:gap-6 sm:px-6 lg:px-8 lg:py-8">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold tracking-[0.14em] text-finance-accent">PERSONAL FINANCE</p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-finance-text sm:text-[28px]">Budgets</h1>
                <p class="mt-1 max-w-2xl text-sm text-finance-muted">Set monthly limits for expense categories and keep track of your spending.</p>
            </div>

            <Button as-child class="h-10 shrink-0 rounded-md bg-finance-action px-4 font-semibold text-finance-action-text hover:bg-finance-action-hover">
                <Link :href="createBudget.url()">
                    <Plus class="size-4" />
                    Add budget
                </Link>
            </Button>
        </header>

        <Card class="min-w-0 gap-0 rounded-lg border-finance-border bg-finance-surface py-0 shadow-none">
            <CardHeader class="border-b border-finance-border px-5 py-4 sm:px-6">
                <div class="flex items-start gap-3">
                    <span class="flex size-9 shrink-0 items-center justify-center border border-finance-border bg-finance-tint text-finance-accent">
                        <CalendarDays class="size-4" />
                    </span>
                    <div class="grid gap-1">
                        <CardTitle class="text-sm font-semibold text-finance-text">Find a month</CardTitle>
                        <CardDescription class="text-xs text-finance-subtle">Filter your budgets by their calendar month.</CardDescription>
                    </div>
                </div>
            </CardHeader>
            <CardContent class="p-4 sm:p-5">
                <form class="flex flex-col gap-3 sm:flex-row sm:items-end" @submit.prevent="applyFilters">
                    <div class="grid w-full gap-2 sm:max-w-xs">
                        <Label for="filter-month" class="text-xs font-medium text-finance-muted">Month</Label>
                        <Input id="filter-month" v-model="filterForm.month" type="month" class="h-10 border-finance-border-strong bg-finance-input text-finance-text" />
                        <InputError :message="filterForm.errors.month" />
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <Button type="submit" class="h-10 bg-finance-action px-4 font-semibold text-finance-action-text hover:bg-finance-action-hover">Apply filter</Button>
                        <Button v-if="hasActiveMonthFilter" type="button" variant="outline" class="h-10 border-finance-border-strong bg-transparent text-finance-body hover:bg-finance-hover hover:text-finance-text" @click="resetFilters">Clear</Button>
                    </div>
                </form>
            </CardContent>
        </Card>

        <Card class="min-w-0 gap-0 rounded-lg border-finance-border bg-finance-surface py-0 shadow-none">
            <CardHeader class="flex flex-col gap-2 border-b border-finance-border px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div class="grid gap-1">
                    <CardTitle class="text-sm font-semibold text-finance-text">Monthly category budgets</CardTitle>
                    <CardDescription class="text-xs text-finance-subtle">
                        {{ budgets.total }} {{ budgets.total === 1 ? 'budget' : 'budgets' }} in your list
                    </CardDescription>
                </div>
                <p v-if="budgets.total > 0" class="text-xs tabular-nums text-finance-subtle">
                    Showing {{ budgets.from }}–{{ budgets.to }} of {{ budgets.total }}
                </p>
            </CardHeader>

            <template v-if="budgets.total > 0">
            <div v-if="budgets.data.length > 0" class="grid gap-4 p-4 sm:grid-cols-2 sm:p-5 xl:grid-cols-3 xl:gap-5 xl:p-6">
                <article v-for="budget in budgets.data" :key="budget.id" class="flex min-w-0 flex-col gap-5 rounded-lg border border-finance-border-soft bg-finance-surface-muted p-4 sm:p-5">
                    <div class="flex min-w-0 items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="relative flex size-10 shrink-0 items-center justify-center border border-finance-border bg-finance-tint text-finance-body">
                                <Tag v-if="!budget.category?.icon" class="size-4" />
                                <span v-else class="max-w-9 truncate px-1 text-[9px] font-semibold tracking-wide" :title="budget.category.icon">{{ budget.category.icon }}</span>
                                <span class="absolute right-1 bottom-1 size-1.5 rounded-full ring-2 ring-finance-surface-muted" :style="{ backgroundColor: budget.category?.color || '#64748b' }" aria-hidden="true" />
                            </span>
                            <div class="min-w-0">
                                <p class="break-words text-sm font-semibold text-finance-text">{{ budget.category?.name ?? 'Unavailable category' }}</p>
                                <p class="mt-1 text-xs capitalize text-finance-subtle">{{ formatMonth(budget.month) }}</p>
                                <p v-if="budget.category?.deleted_at || budget.category?.type !== 'expense'" class="mt-1 text-[10px] font-semibold tracking-wide text-finance-subtle">CATEGORY UNAVAILABLE</p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-1.5">
                            <Button as-child variant="outline" size="sm" class="size-9 border-finance-border-strong bg-transparent p-0 text-finance-body hover:bg-finance-hover hover:text-finance-text">
                                <Link :href="edit.url({ budget: budget.id })" :aria-label="`Edit ${budget.category?.name ?? 'budget'} budget`">
                                    <Edit class="size-4" />
                                </Link>
                            </Button>
                            <ConfirmDialog
                                title="Delete budget"
                                description="Delete this monthly budget? Your transactions will not be changed."
                                variant="destructive"
                                confirm-text="Delete budget"
                                @confirm="handleDelete(budget.id)"
                            >
                                <template #trigger>
                                    <Button variant="outline" size="sm" class="size-9 border-finance-border-strong bg-transparent p-0 text-finance-muted hover:border-finance-negative-border hover:bg-finance-negative-soft hover:text-finance-negative" :disabled="deletingBudgetId === budget.id" :aria-label="`Delete ${budget.category?.name ?? 'budget'} budget`">
                                        <LoaderCircle v-if="deletingBudgetId === budget.id" class="size-4 animate-spin" />
                                        <Trash2 v-else class="size-4" />
                                    </Button>
                                </template>
                            </ConfirmDialog>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <div class="flex items-end justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[10px] font-semibold tracking-[0.12em] text-finance-subtle">SPENT</p>
                                <p class="mt-1 truncate text-lg font-semibold tabular-nums text-finance-text">{{ formatCurrency(budget.spent_amount) }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-[10px] font-semibold tracking-[0.12em] text-finance-subtle">MONTHLY LIMIT</p>
                                <p class="mt-1 text-sm font-medium tabular-nums text-finance-body">{{ formatCurrency(budget.amount) }}</p>
                            </div>
                        </div>

                        <div
                            class="h-2.5 overflow-hidden rounded-full bg-finance-tint"
                            role="progressbar"
                            :aria-label="`${budget.category?.name ?? 'Budget'} budget usage`"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            :aria-valuenow="progressWidth(budget)"
                        >
                            <div
                                class="h-full rounded-full transition-[width]"
                                :class="getUsageState(budget) === 'over-budget' ? 'bg-finance-negative' : getUsageState(budget) === 'near-limit' ? 'bg-amber-500' : 'bg-finance-accent'"
                                :style="{ width: `${progressWidth(budget)}%` }"
                            />
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span
                                class="inline-flex items-center gap-1.5 text-xs font-semibold"
                                :class="getUsageState(budget) === 'over-budget' ? 'text-finance-negative' : getUsageState(budget) === 'near-limit' ? 'text-amber-600 dark:text-amber-400' : 'text-finance-positive'"
                            >
                                <AlertTriangle v-if="getUsageState(budget) !== 'on-track'" class="size-3.5" />
                                <Check v-else class="size-3.5" />
                                {{ getUsageState(budget) === 'over-budget' ? 'Over budget' : getUsageState(budget) === 'near-limit' ? 'Approaching limit' : 'On track' }}
                            </span>
                            <span class="text-xs font-semibold tabular-nums text-finance-body">{{ budget.usage_percentage }}% used</span>
                        </div>

                        <div class="flex items-center justify-between gap-3 border-t border-finance-border-soft pt-3 text-xs">
                            <span class="text-finance-subtle">{{ budget.remaining_amount < 0 ? 'Over by' : 'Remaining' }}</span>
                            <span class="font-semibold tabular-nums" :class="budget.remaining_amount < 0 ? 'text-finance-negative' : 'text-finance-text'">{{ formatCurrency(Math.abs(budget.remaining_amount)) }}</span>
                        </div>
                    </div>
                </article>
            </div>

            <CardContent v-else class="px-5 py-10 text-center text-sm text-finance-subtle">
                No budgets on this page. Use the pagination controls to view your budgets.
            </CardContent>

            <footer v-if="budgets.last_page > 1" class="flex flex-col gap-3 border-t border-finance-border px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p class="text-xs text-finance-subtle">
                    Showing <span class="tabular-nums text-finance-body">{{ budgets.from }}–{{ budgets.to }}</span>
                    of <span class="tabular-nums text-finance-body">{{ budgets.total }}</span> budgets
                </p>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" class="h-9 border-finance-border-strong bg-transparent px-3 text-finance-body hover:bg-finance-hover hover:text-finance-text" :disabled="!budgets.prev_page_url" @click="router.visit(budgets.prev_page_url || '')">
                        <ArrowLeft class="size-4" />
                        Previous
                    </Button>
                    <span class="px-1 text-xs tabular-nums text-finance-subtle">{{ budgets.current_page }} / {{ budgets.last_page }}</span>
                    <Button variant="outline" size="sm" class="h-9 border-finance-border-strong bg-transparent px-3 text-finance-body hover:bg-finance-hover hover:text-finance-text" :disabled="!budgets.next_page_url" @click="router.visit(budgets.next_page_url || '')">
                        Next
                        <ArrowRight class="size-4" />
                    </Button>
                </div>
            </footer>
            </template>

            <CardContent v-else class="flex flex-col items-center px-5 py-12 text-center sm:py-16">
                <span class="flex size-12 items-center justify-center border border-finance-border bg-finance-tint text-finance-muted">
                    <WalletCards v-if="categories.length > 0" class="size-5" />
                    <FolderOpen v-else class="size-5" />
                </span>
                <p class="mt-4 text-sm font-medium text-finance-text">{{ hasActiveMonthFilter ? 'No budgets for this month' : categories.length > 0 ? 'No budgets yet' : 'Create an expense category first' }}</p>
                <p class="mt-1 max-w-sm text-xs leading-5 text-finance-subtle">
                    {{ hasActiveMonthFilter ? 'Try another month or clear the filter to see your other budgets.' : categories.length > 0 ? 'Create a monthly budget to keep an eye on category spending.' : 'Budgets are linked to expense categories. Add one before creating your first budget.' }}
                </p>
                <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                    <Button v-if="hasActiveMonthFilter" variant="outline" class="h-9 border-finance-border-strong bg-transparent px-3.5 text-xs font-semibold text-finance-body hover:bg-finance-hover hover:text-finance-text" @click="resetFilters">Clear month filter</Button>
                    <Button v-if="categories.length > 0" as-child class="h-9 rounded-md bg-finance-action px-3.5 text-xs font-semibold text-finance-action-text hover:bg-finance-action-hover">
                        <Link :href="createBudget.url()">
                            <Plus class="size-4" />
                            Add budget
                        </Link>
                    </Button>
                    <Button v-else as-child variant="outline" class="h-9 border-finance-border-strong bg-transparent px-3.5 text-xs font-semibold text-finance-body hover:bg-finance-hover hover:text-finance-text">
                        <Link :href="categoriesIndex.url()">
                            <Plus class="size-4" />
                            Add expense category
                        </Link>
                    </Button>
                </div>
            </CardContent>
        </Card>
    </main>
</template>
