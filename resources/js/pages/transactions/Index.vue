<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    ArrowDownRight,
    ArrowLeft,
    ArrowRight,
    ArrowUpRight,
    CalendarDays,
    Edit,
    Plus,
    Receipt,
    RotateCcw,
    Search,
    Trash2,
} from '@lucide/vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import MoneyAmount from '@/components/MoneyAmount.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { create as createTransaction, destroy as destroyTransaction, edit, index as transactionsIndex } from '@/routes/transactions';
import type { Category, PaginatedResponse, Transaction } from '@/types';

interface Props {
    transactions: PaginatedResponse<Transaction> & {
        current_page: number;
        last_page: number;
        from: number | null;
        to: number | null;
        total: number;
    };
    categories: Category[];
    filters: {
        type?: string;
        category_id?: string;
        start_date?: string;
        end_date?: string;
    };
}

const props = defineProps<Props>();

const allTypesValue = '__all_types__';
const allCategoriesValue = '__all_categories__';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Transactions',
                href: transactionsIndex.url(),
            },
        ],
    },
});

const deletingTransactionId = ref<number | null>(null);

const handleDelete = (transactionId: number): void => {
    deletingTransactionId.value = transactionId;

    router.delete(destroyTransaction.url({ transaction: transactionId }), {
        onSuccess: () => {
            deletingTransactionId.value = null;
        },
        onError: () => {
            deletingTransactionId.value = null;
        },
    });
};

const filterForm = useForm({
    type: props.filters.type || allTypesValue,
    category_id: props.filters.category_id || allCategoriesValue,
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

const hasActiveFilters = computed(() => Boolean(
    props.filters.type || props.filters.category_id || props.filters.start_date || props.filters.end_date,
));

const applyFilters = (): void => {
    const filters = filterForm.data();

    router.get(transactionsIndex.url(), {
        ...filters,
        type: filters.type === allTypesValue ? '' : filters.type,
        category_id: filters.category_id === allCategoriesValue ? '' : filters.category_id,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = (): void => {
    filterForm.type = allTypesValue;
    filterForm.category_id = allCategoriesValue;
    filterForm.start_date = '';
    filterForm.end_date = '';

    router.get(transactionsIndex.url(), {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

const getCategoryName = (categoryId?: number | null): string => {
    if (!categoryId) return 'Uncategorized';
    const category = props.categories.find((item) => item.id === categoryId);

    return category?.name ?? 'Unknown category';
};

const getCategoryColor = (categoryId?: number | null): string => {
    if (!categoryId) return '#64748b';
    const category = props.categories.find((item) => item.id === categoryId);

    return category?.color || '#64748b';
};

const formatDate = (date: string): string => {
    const [year, month, day] = date.slice(0, 10).split('-').map(Number);

    return new Intl.DateTimeFormat('el-GR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(year, month - 1, day));
};
</script>

<template>
    <Head title="Transactions" />

    <main class="mx-auto flex min-h-full w-full min-w-0 max-w-[1600px] flex-1 flex-col gap-5 px-4 py-6 sm:gap-6 sm:px-6 lg:px-8 lg:py-8">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold tracking-[0.14em] text-finance-accent">PERSONAL FINANCE</p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-finance-text sm:text-[28px]">Transactions</h1>
                <p class="mt-1 text-sm text-finance-muted">Review and manage your financial activity.</p>
            </div>

            <Button as-child class="h-10 shrink-0 rounded-md bg-finance-action px-4 font-semibold text-finance-action-text hover:bg-finance-action-hover">
                <Link :href="createTransaction.url()">
                    <Plus class="size-4" />
                    Add transaction
                </Link>
            </Button>
        </header>

        <Card class="gap-0 rounded-lg border-finance-border bg-finance-surface py-0 shadow-none">
            <CardHeader class="flex flex-row items-center gap-3 border-b border-finance-border px-5 py-4 sm:px-6">
                <span class="flex size-9 shrink-0 items-center justify-center border border-finance-accent bg-finance-accent-soft text-finance-accent">
                    <Search class="size-4" />
                </span>
                <div class="min-w-0">
                    <CardTitle class="text-sm font-semibold text-finance-text">Filter transactions</CardTitle>
                    <CardDescription class="mt-1 text-xs text-finance-subtle">Narrow your activity by type, category, or date.</CardDescription>
                </div>
            </CardHeader>

            <CardContent class="px-5 py-5 sm:px-6">
                <form class="grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-[1fr_1.2fr_1fr_1fr_auto] xl:items-end" @submit.prevent="applyFilters">
                    <div class="grid min-w-0 gap-2">
                        <Label for="type" class="text-xs font-medium text-finance-muted">Type</Label>
                        <Select v-model="filterForm.type">
                            <SelectTrigger id="type" class="h-10 w-full border-finance-border-strong bg-finance-bg text-finance-text">
                                <SelectValue placeholder="All types" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="allTypesValue">All types</SelectItem>
                                <SelectItem value="income">Income</SelectItem>
                                <SelectItem value="expense">Expense</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="grid min-w-0 gap-2">
                        <Label for="category_id" class="text-xs font-medium text-finance-muted">Category</Label>
                        <Select v-model="filterForm.category_id">
                            <SelectTrigger id="category_id" class="h-10 w-full border-finance-border-strong bg-finance-bg text-finance-text">
                                <SelectValue placeholder="All categories" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="allCategoriesValue">All categories</SelectItem>
                                <SelectItem
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="category.id.toString()"
                                >
                                    {{ category.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="grid min-w-0 gap-2">
                        <Label for="start_date" class="text-xs font-medium text-finance-muted">From</Label>
                        <div class="relative">
                            <CalendarDays class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-finance-subtle" />
                            <Input
                                id="start_date"
                                v-model="filterForm.start_date"
                                type="date"
                                class="h-10 border-finance-border-strong bg-finance-bg pl-9 text-finance-text "
                            />
                        </div>
                    </div>

                    <div class="grid min-w-0 gap-2">
                        <Label for="end_date" class="text-xs font-medium text-finance-muted">To</Label>
                        <div class="relative">
                            <CalendarDays class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-finance-subtle" />
                            <Input
                                id="end_date"
                                v-model="filterForm.end_date"
                                type="date"
                                class="h-10 border-finance-border-strong bg-finance-bg pl-9 text-finance-text "
                            />
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 sm:col-span-2 xl:col-span-1">
                        <Button type="submit" class="h-10 flex-1 rounded-md bg-finance-action px-4 font-semibold text-finance-action-text hover:bg-finance-action-hover sm:flex-none">
                            <Search class="size-4" />
                            Apply
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            class="h-10 border-finance-border-strong bg-transparent px-3 text-finance-body hover:bg-finance-hover hover:text-finance-text"
                            :disabled="!hasActiveFilters"
                            @click="resetFilters"
                        >
                            <RotateCcw class="size-4" />
                            <span class="sm:hidden xl:inline">Reset</span>
                            <span class="hidden sm:inline xl:hidden">Clear filters</span>
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>

        <Card class="min-w-0 gap-0 rounded-lg border-finance-border bg-finance-surface py-0 shadow-none">
            <CardHeader class="flex flex-col gap-3 border-b border-finance-border px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div class="grid gap-1">
                    <CardTitle class="text-sm font-semibold text-finance-text">All transactions</CardTitle>
                    <CardDescription class="text-xs text-finance-subtle">
                        {{ transactions.total }} {{ transactions.total === 1 ? 'transaction' : 'transactions' }} found
                    </CardDescription>
                </div>
                <p v-if="transactions.total > 0" class="text-xs tabular-nums text-finance-subtle">
                    Showing {{ transactions.from }}–{{ transactions.to }} of {{ transactions.total }}
                </p>
            </CardHeader>

            <template v-if="transactions.data.length > 0">
                <!-- Mobile and tablet list -->
                <div class="divide-y divide-finance-border-soft xl:hidden">
                    <article
                        v-for="transaction in transactions.data"
                        :key="transaction.id"
                        class="grid min-w-0 gap-3 px-5 py-4 sm:px-6"
                    >
                        <div class="flex min-w-0 items-start justify-between gap-3">
                            <div class="flex min-w-0 items-start gap-3">
                                <span
                                    class="mt-0.5 flex size-8 shrink-0 items-center justify-center border"
                                    :class="transaction.type === 'income'
                                        ? 'border-finance-accent bg-finance-accent-soft text-finance-accent'
                                        : 'border-finance-negative-border bg-finance-negative-soft text-finance-negative'"
                                >
                                    <ArrowDownRight v-if="transaction.type === 'income'" class="size-4" />
                                    <ArrowUpRight v-else class="size-4" />
                                </span>
                                <div class="min-w-0">
                                    <p class="break-words text-sm font-medium text-finance-text">{{ transaction.description }}</p>
                                    <div class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-finance-subtle">
                                        <span class="inline-flex min-w-0 items-center gap-1.5">
                                            <span class="size-1.5 shrink-0 rounded-full" :style="{ backgroundColor: getCategoryColor(transaction.category_id) }" />
                                            <span class="break-words">{{ getCategoryName(transaction.category_id) }}</span>
                                        </span>
                                        <span class="text-finance-faint">·</span>
                                        <span>{{ formatDate(transaction.transaction_date) }}</span>
                                    </div>
                                </div>
                            </div>
                            <MoneyAmount class="shrink-0 text-right text-sm font-semibold" :amount="transaction.amount" :type="transaction.type" />
                        </div>

                        <div class="flex items-center justify-between gap-3 pl-11">
                            <span
                                class="text-[10px] font-semibold tracking-wide"
                                :class="transaction.type === 'income' ? 'text-finance-accent' : 'text-finance-negative'"
                            >
                                {{ transaction.type === 'income' ? 'INCOME' : 'EXPENSE' }}
                            </span>
                            <div class="flex items-center gap-2">
                                <Button as-child variant="outline" size="sm" class="size-9 border-finance-border-strong bg-transparent p-0 text-finance-body hover:bg-finance-hover hover:text-finance-text">
                                    <Link :href="edit.url({ transaction: transaction.id })" :aria-label="`Edit ${transaction.description}`">
                                        <Edit class="size-4" />
                                    </Link>
                                </Button>
                                <ConfirmDialog
                                    title="Delete transaction"
                                    description="Are you sure you want to delete this transaction? This action cannot be undone."
                                    variant="destructive"
                                    confirm-text="Delete transaction"
                                    @confirm="handleDelete(transaction.id)"
                                >
                                    <template #trigger>
                                        <Button variant="outline" size="sm" class="size-9 border-finance-border-strong bg-transparent p-0 text-finance-muted hover:border-finance-negative-border hover:bg-finance-negative-soft hover:text-finance-negative" :aria-label="`Delete ${transaction.description}`" :disabled="deletingTransactionId === transaction.id">
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </template>
                                </ConfirmDialog>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Desktop table -->
                <div class="hidden min-w-0 overflow-x-auto xl:block">
                    <table class="w-full min-w-[860px] text-left">
                        <thead class="border-b border-finance-border-soft text-[10px] font-semibold tracking-[0.12em] text-finance-subtle">
                            <tr>
                                <th class="px-6 py-3 font-semibold">DESCRIPTION</th>
                                <th class="px-4 py-3 font-semibold">CATEGORY</th>
                                <th class="px-4 py-3 font-semibold">DATE</th>
                                <th class="px-4 py-3 font-semibold">TYPE</th>
                                <th class="px-4 py-3 text-right font-semibold">AMOUNT</th>
                                <th class="w-28 px-6 py-3 text-right font-semibold">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-finance-border-soft">
                            <tr v-for="transaction in transactions.data" :key="transaction.id" class="transition-colors hover:bg-finance-hover">
                                <td class="max-w-[360px] px-6 py-4">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span
                                            class="flex size-8 shrink-0 items-center justify-center border"
                                            :class="transaction.type === 'income'
                                                ? 'border-finance-accent bg-finance-accent-soft text-finance-accent'
                                                : 'border-finance-negative-border bg-finance-negative-soft text-finance-negative'"
                                        >
                                            <ArrowDownRight v-if="transaction.type === 'income'" class="size-4" />
                                            <ArrowUpRight v-else class="size-4" />
                                        </span>
                                        <span class="truncate text-sm font-medium text-finance-text">{{ transaction.description }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex max-w-48 items-center gap-2 truncate text-xs text-finance-muted">
                                        <span class="size-1.5 shrink-0 rounded-full" :style="{ backgroundColor: getCategoryColor(transaction.category_id) }" />
                                        <span class="truncate">{{ getCategoryName(transaction.category_id) }}</span>
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-xs text-finance-subtle">{{ formatDate(transaction.transaction_date) }}</td>
                                <td class="px-4 py-4">
                                    <span class="text-[10px] font-semibold tracking-wide" :class="transaction.type === 'income' ? 'text-finance-accent' : 'text-finance-negative'">
                                        {{ transaction.type === 'income' ? 'INCOME' : 'EXPENSE' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-right">
                                    <MoneyAmount class="text-sm font-semibold" :amount="transaction.amount" :type="transaction.type" />
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button as-child variant="outline" size="sm" class="size-9 border-finance-border-strong bg-transparent p-0 text-finance-body hover:bg-finance-hover hover:text-finance-text">
                                            <Link :href="edit.url({ transaction: transaction.id })" :aria-label="`Edit ${transaction.description}`">
                                                <Edit class="size-4" />
                                            </Link>
                                        </Button>
                                        <ConfirmDialog
                                            title="Delete transaction"
                                            description="Are you sure you want to delete this transaction? This action cannot be undone."
                                            variant="destructive"
                                            confirm-text="Delete transaction"
                                            @confirm="handleDelete(transaction.id)"
                                        >
                                            <template #trigger>
                                                <Button variant="outline" size="sm" class="size-9 border-finance-border-strong bg-transparent p-0 text-finance-muted hover:border-finance-negative-border hover:bg-finance-negative-soft hover:text-finance-negative" :aria-label="`Delete ${transaction.description}`" :disabled="deletingTransactionId === transaction.id">
                                                    <Trash2 class="size-4" />
                                                </Button>
                                            </template>
                                        </ConfirmDialog>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <footer v-if="transactions.last_page > 1" class="flex flex-col gap-3 border-t border-finance-border px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <p class="text-xs text-finance-subtle">
                        Showing <span class="tabular-nums text-finance-body">{{ transactions.from }}–{{ transactions.to }}</span>
                        of <span class="tabular-nums text-finance-body">{{ transactions.total }}</span> transactions
                    </p>
                    <div class="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            class="h-9 border-finance-border-strong bg-transparent px-3 text-finance-body hover:bg-finance-hover hover:text-finance-text"
                            :disabled="!transactions.links.prev"
                            @click="router.visit(transactions.links.prev || '')"
                        >
                            <ArrowLeft class="size-4" />
                            Previous
                        </Button>
                        <span class="px-1 text-xs tabular-nums text-finance-subtle">{{ transactions.current_page }} / {{ transactions.last_page }}</span>
                        <Button
                            variant="outline"
                            size="sm"
                            class="h-9 border-finance-border-strong bg-transparent px-3 text-finance-body hover:bg-finance-hover hover:text-finance-text"
                            :disabled="!transactions.links.next"
                            @click="router.visit(transactions.links.next || '')"
                        >
                            Next
                            <ArrowRight class="size-4" />
                        </Button>
                    </div>
                </footer>
            </template>

            <CardContent v-else class="flex flex-col items-center px-5 py-12 text-center sm:py-16">
                <span class="flex size-12 items-center justify-center border border-finance-border bg-finance-tint text-finance-muted">
                    <Receipt class="size-5" />
                </span>
                <p class="mt-4 text-sm font-medium text-finance-text">{{ hasActiveFilters ? 'No transactions match these filters' : 'No transactions yet' }}</p>
                <p class="mt-1 max-w-sm text-xs leading-5 text-finance-subtle">
                    {{ hasActiveFilters ? 'Try clearing one or more filters, or add a new transaction.' : 'Add your first transaction to start tracking your activity.' }}
                </p>
                <div class="mt-5 flex flex-wrap justify-center gap-2">
                    <Button v-if="hasActiveFilters" variant="outline" class="h-9 border-finance-border-strong bg-transparent text-finance-body hover:bg-finance-hover hover:text-finance-text" @click="resetFilters">
                        <RotateCcw class="size-4" />
                        Clear filters
                    </Button>
                    <Button as-child class="h-9 rounded-md bg-finance-action px-3.5 text-xs font-semibold text-finance-action-text hover:bg-finance-action-hover">
                        <Link :href="createTransaction.url()">
                            <Plus class="size-4" />
                            Add transaction
                        </Link>
                    </Button>
                </div>
            </CardContent>
        </Card>
    </main>
</template>
