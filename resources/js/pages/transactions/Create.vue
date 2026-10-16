<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { AcceptableValue } from 'reka-ui';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { store as storeTransaction, index as transactionsRoute, create as createTransaction } from '@/routes/transactions';
import InputError from '@/components/InputError.vue';
import type { Category } from '@/types';

interface Props {
    categories: Category[];
}

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Transactions',
                href: transactionsRoute.url(),
            },
            {
                title: 'Create transaction',
                href: createTransaction.url(),
            },
        ],
    },
});

const noCategoryValue = '__none__';

const form = useForm({
    category_id: '',
    type: 'expense',
    amount: '',
    transaction_date: new Date().toISOString().split('T')[0],
    description: '',
    notes: '',
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        category_id: data.category_id === noCategoryValue ? '' : data.category_id,
    })).post(storeTransaction.url(), {
        onSuccess: () => {
            form.reset();
        },
    });
};

const typeOptions = [
    { value: 'income', label: 'Income' },
    { value: 'expense', label: 'Expense' },
];

// Filter categories based on selected type
const filteredCategories = computed(() => {
    return props.categories.filter(category => category.type === form.type);
});

// Update category when type changes if current category doesn't match
const handleTypeChange = (value: AcceptableValue) => {
    if (value !== 'income' && value !== 'expense') {
        return;
    }

    const newType = value;
    form.type = newType;

    // If current category doesn't match the new type, clear it
    if (form.category_id) {
        const currentCategory = props.categories.find(
            cat => cat.id === parseInt(form.category_id)
        );

        if (currentCategory && currentCategory.type !== newType) {
            form.category_id = '';
        }
    }
};
</script>

<template>
    <Head title="Create Transaction" />

    <main class="mx-auto flex w-full min-w-0 max-w-4xl flex-1 flex-col gap-5 px-4 py-6 sm:gap-6 sm:px-6 lg:py-8">
        <div class="min-w-0">
            <p class="text-xs font-semibold tracking-[0.14em] text-finance-accent">PERSONAL FINANCE</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-finance-text sm:text-[28px]">Create transaction</h1>
            <p class="mt-1 text-sm text-finance-muted">Record income or an expense in your account.</p>
        </div>

        <Card class="gap-0 rounded-lg border-finance-border bg-finance-surface py-0 shadow-none">
            <CardHeader class="border-b border-finance-border px-5 py-4 sm:px-6">
                <CardTitle class="text-sm font-semibold text-finance-text">Transaction details</CardTitle>
                <CardDescription class="mt-1 text-xs text-finance-subtle">Add the amount, date, and a short description.</CardDescription>
            </CardHeader>
            <CardContent class="px-5 py-5 sm:px-6 sm:py-6">
                <form @submit.prevent="submit" class="space-y-5 sm:space-y-6">
                    <div class="grid min-w-0 grid-cols-1 gap-5 sm:grid-cols-2 sm:gap-6">
                        <div class="space-y-2">
                            <Label for="type" class="text-xs font-medium text-finance-muted">Type *</Label>
                            <Select v-model="form.type" @update:model-value="handleTypeChange">
                                <SelectTrigger id="type" class="h-10 w-full border-finance-border-strong bg-finance-bg text-finance-text">
                                    <SelectValue placeholder="Select type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in typeOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="flex items-center gap-2 text-xs text-finance-subtle">
                                <span :class="form.type === 'income' ? 'bg-finance-action' : 'bg-finance-negative'" class="h-1.5 w-1.5 rounded-full"></span>
                                {{ form.type === 'income' ? 'Income adds to your balance.' : 'Expense is deducted from your balance.' }}
                            </p>
                            <InputError :message="form.errors.type" />
                        </div>

                        <div class="space-y-2">
                            <Label for="transaction_date" class="text-xs font-medium text-finance-muted">Date *</Label>
                            <Input
                                id="transaction_date"
                                v-model="form.transaction_date"
                                type="date"
                                class="h-10 border-finance-border-strong bg-finance-bg text-finance-text "
                                required
                            />
                            <InputError :message="form.errors.transaction_date" />
                        </div>
                    </div>

                    <div class="grid min-w-0 grid-cols-1 gap-5 sm:grid-cols-2 sm:gap-6">
                        <div class="space-y-2">
                            <Label for="amount" class="text-xs font-medium text-finance-muted">Amount *</Label>
                            <Input
                                id="amount"
                                v-model="form.amount"
                                type="number"
                                step="0.01"
                                placeholder="0.00"
                                class="h-10 border-finance-border-strong bg-finance-bg text-finance-text placeholder:text-finance-faint"
                                required
                            />
                            <InputError :message="form.errors.amount" />
                        </div>

                        <div class="space-y-2">
                            <Label for="description" class="text-xs font-medium text-finance-muted">Description *</Label>
                            <Input
                                id="description"
                                v-model="form.description"
                                type="text"
                                placeholder="Groceries, Salary, etc."
                                class="h-10 border-finance-border-strong bg-finance-bg text-finance-text placeholder:text-finance-faint"
                                required
                            />
                            <InputError :message="form.errors.description" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="category_id" class="text-xs font-medium text-finance-muted">Category</Label>
                        <Select v-model="form.category_id">
                            <SelectTrigger id="category_id" class="h-10 w-full border-finance-border-strong bg-finance-bg text-finance-text">
                                <SelectValue placeholder="Select category" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="noCategoryValue">None</SelectItem>
                                <SelectItem
                                    v-for="category in filteredCategories"
                                    :key="category.id"
                                    :value="category.id.toString()"
                                >
                                    {{ category.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.category_id" />
                    </div>

                    <div class="space-y-2">
                        <Label for="notes" class="text-xs font-medium text-finance-muted">Notes</Label>
                        <Textarea
                            id="notes"
                            v-model="form.notes"
                            placeholder="Additional details about this transaction"
                            rows="3"
                            class="min-h-24 border-finance-border-strong bg-finance-bg text-finance-text placeholder:text-finance-faint"
                        />
                        <InputError :message="form.errors.notes" />
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-finance-border pt-5 sm:flex-row sm:justify-end">
                        <Button variant="outline" class="h-10 border-finance-border-strong bg-transparent text-finance-body hover:bg-finance-hover hover:text-finance-text" as-child>
                            <Link :href="transactionsRoute.url()">Cancel</Link>
                        </Button>
                        <Button type="submit" class="h-10 bg-finance-action px-4 font-semibold text-finance-action-text hover:bg-finance-action-hover" :disabled="form.processing">
                            {{ form.processing ? 'Creating…' : 'Create transaction' }}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </main>
</template>
