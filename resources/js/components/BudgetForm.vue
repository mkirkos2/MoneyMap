<script setup lang="ts">
import { computed } from 'vue';
import type { AcceptableValue } from 'reka-ui';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CalendarDays, WalletCards } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { index as budgetsIndex, store as storeBudget, update as updateBudget } from '@/routes/budgets';
import { create as createCategory } from '@/routes/categories';
import type { Budget, Category } from '@/types';

interface Props {
    categories: Category[];
    budget?: Budget;
    month?: string;
}

const props = defineProps<Props>();
const isEditing = computed(() => props.budget !== undefined);

const form = useForm({
    category_id: props.budget ? String(props.budget.category_id) : '',
    month: props.budget?.month ?? props.month ?? '',
    amount: props.budget ? String(props.budget.amount) : '',
});

const submit = (): void => {
    if (props.budget) {
        form.put(updateBudget.url({ budget: props.budget.id }));

        return;
    }

    form.post(storeBudget.url());
};

const updateCategory = (value: AcceptableValue): void => {
    form.category_id = typeof value === 'string' || typeof value === 'number' ? String(value) : '';
};
</script>

<template>
    <Head :title="isEditing ? 'Edit budget' : 'Create budget'" />

    <main class="mx-auto flex w-full min-w-0 max-w-3xl flex-1 flex-col gap-5 px-4 py-6 sm:gap-6 sm:px-6 lg:py-8">
        <header class="min-w-0">
            <p class="text-xs font-semibold tracking-[0.14em] text-finance-accent">PERSONAL FINANCE</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-finance-text sm:text-[28px]">
                {{ isEditing ? 'Edit monthly budget' : 'Create monthly budget' }}
            </h1>
            <p class="mt-1 text-sm text-finance-muted">
                Set a monthly spending limit for one of your expense categories.
            </p>
        </header>

        <Card class="min-w-0 gap-0 rounded-lg border-finance-border bg-finance-surface py-0 shadow-none">
            <CardHeader class="border-b border-finance-border px-5 py-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <span class="flex size-10 shrink-0 items-center justify-center border border-finance-border bg-finance-tint text-finance-accent">
                        <WalletCards class="size-5" />
                    </span>
                    <div class="grid gap-1">
                        <CardTitle class="text-sm font-semibold text-finance-text">
                            {{ isEditing ? 'Budget details' : 'New budget' }}
                        </CardTitle>
                        <CardDescription class="text-xs text-finance-subtle">
                            Spending is calculated from your expense transactions for the selected month.
                        </CardDescription>
                    </div>
                </div>
            </CardHeader>

            <CardContent class="p-5 sm:p-6">
                <form class="grid gap-5" @submit.prevent="submit">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="space-y-2 sm:col-span-2">
                            <Label for="category_id" class="text-xs font-medium text-finance-muted">Expense category</Label>
                            <Select :model-value="form.category_id" @update:model-value="updateCategory">
                                <SelectTrigger id="category_id" class="h-10 border-finance-border-strong bg-finance-input text-finance-text">
                                    <SelectValue placeholder="Choose a category" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="category in categories" :key="category.id" :value="String(category.id)">
                                        {{ category.name }}
                                        <span v-if="category.deleted_at || category.type !== 'expense'" class="text-finance-subtle">(unavailable)</span>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.category_id" />
                            <p v-if="categories.length === 0" class="text-xs leading-5 text-finance-subtle">
                                You need an expense category before setting a budget.
                                <Link :href="createCategory.url()" class="font-medium text-finance-accent underline underline-offset-4">Create a category</Link>
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="month" class="text-xs font-medium text-finance-muted">Budget month</Label>
                            <div class="relative">
                                <CalendarDays class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-finance-subtle" />
                                <Input id="month" v-model="form.month" type="month" required class="h-10 border-finance-border-strong bg-finance-input pl-10 text-finance-text" />
                            </div>
                            <InputError :message="form.errors.month" />
                        </div>

                        <div class="space-y-2">
                            <Label for="amount" class="text-xs font-medium text-finance-muted">Monthly limit</Label>
                            <div class="relative">
                                <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-finance-subtle">€</span>
                                <Input id="amount" v-model="form.amount" type="number" min="0.01" step="0.01" required placeholder="0.00" class="h-10 border-finance-border-strong bg-finance-input pl-8 text-finance-text" />
                            </div>
                            <InputError :message="form.errors.amount" />
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-finance-border pt-5 sm:flex-row sm:justify-end">
                        <Button variant="outline" class="h-10 border-finance-border-strong bg-transparent text-finance-body hover:bg-finance-hover hover:text-finance-text" as-child>
                            <Link :href="budgetsIndex.url()">
                                <ArrowLeft class="size-4" />
                                Cancel
                            </Link>
                        </Button>
                        <Button type="submit" class="h-10 bg-finance-action px-4 font-semibold text-finance-action-text hover:bg-finance-action-hover" :disabled="form.processing || categories.length === 0">
                            {{ form.processing ? 'Saving…' : isEditing ? 'Save changes' : 'Create budget' }}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </main>
</template>
