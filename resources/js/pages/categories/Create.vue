<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { create as createCategory, store as storeCategory } from '@/routes/categories';
import { index as categoriesRoute } from '@/routes/categories';
import InputError from '@/components/InputError.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Categories',
                href: categoriesRoute.url(),
            },
            {
                title: 'Create Category',
                href: createCategory.url(),
            },
        ],
    },
});

const form = useForm({
    name: '',
    type: 'expense',
    color: '#3b82f6', // Default blue color
    icon: '',
});

const submit = () => {
    form.post(storeCategory.url(), {
        onSuccess: () => {
            form.reset();
        },
    });
};

const typeOptions = [
    { value: 'income', label: 'Income' },
    { value: 'expense', label: 'Expense' },
];

// Predefined color options
const colorOptions = [
    { value: '#3b82f6', label: 'Blue', class: 'bg-blue-500' },
    { value: '#10b981', label: 'Green', class: 'bg-green-500' },
    { value: '#ef4444', label: 'Red', class: 'bg-red-500' },
    { value: '#f59e0b', label: 'Amber', class: 'bg-amber-500' },
    { value: '#8b5cf6', label: 'Purple', class: 'bg-purple-500' },
    { value: '#ec4899', label: 'Pink', class: 'bg-pink-500' },
];
</script>

<template>
    <Head title="Create Category" />

    <main class="mx-auto flex w-full min-w-0 max-w-3xl flex-1 flex-col gap-5 px-4 py-6 sm:gap-6 sm:px-6 lg:py-8">
        <div class="min-w-0">
            <p class="text-xs font-semibold tracking-[0.14em] text-finance-accent">PERSONAL FINANCE</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-finance-text sm:text-[28px]">Create category</h1>
            <p class="mt-1 text-sm text-finance-muted">Add a category to organize your transactions.</p>
        </div>

        <Card class="gap-0 rounded-lg border-finance-border bg-finance-surface py-0 shadow-none">
            <CardHeader class="border-b border-finance-border px-5 py-4 sm:px-6">
                <CardTitle class="text-sm font-semibold text-finance-text">Category details</CardTitle>
                <CardDescription class="mt-1 text-xs text-finance-subtle">Name the category and choose how it appears.</CardDescription>
            </CardHeader>
            <CardContent class="px-5 py-5 sm:px-6 sm:py-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <Label for="name" class="text-xs font-medium text-finance-muted">Name *</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="Food, Salary, etc."
                            class="h-10 border-finance-border-strong bg-finance-bg text-finance-text placeholder:text-finance-faint"
                            required
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="space-y-2">
                        <Label for="type" class="text-xs font-medium text-finance-muted">Type *</Label>
                        <Select v-model="form.type">
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
                        <InputError :message="form.errors.type" />
                    </div>

                    <div class="space-y-2">
                        <Label for="icon" class="text-xs font-medium text-finance-muted">Icon</Label>
                        <Input
                            id="icon"
                            v-model="form.icon"
                            type="text"
                            placeholder="e.g. utensils"
                            class="h-10 border-finance-border-strong bg-finance-bg text-finance-text placeholder:text-finance-faint"
                        />
                        <InputError :message="form.errors.icon" />
                    </div>

                    <div class="space-y-2">
                        <Label class="text-xs font-medium text-finance-muted">Color</Label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="color in colorOptions"
                                :key="color.value"
                                type="button"
                                :class="[
                                    'h-8 w-8 rounded-full',
                                    color.class,
                                    form.color === color.value ? 'ring-2 ring-finance-accent ring-offset-2 ring-offset-finance-surface' : ''
                                ]"
                                @click="form.color = color.value"
                                :aria-label="`${color.label} color`"
                                :aria-pressed="form.color === color.value"
                            ></button>
                        </div>
                        <InputError :message="form.errors.color" />
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-finance-border pt-5 sm:flex-row sm:justify-end">
                        <Button variant="outline" class="h-10 border-finance-border-strong bg-transparent text-finance-body hover:bg-finance-hover hover:text-finance-text" as-child>
                            <Link :href="categoriesRoute.url()">Cancel</Link>
                        </Button>
                        <Button type="submit" class="h-10 bg-finance-action px-4 font-semibold text-finance-action-text hover:bg-finance-action-hover" :disabled="form.processing">
                            {{ form.processing ? 'Creating…' : 'Create category' }}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </main>
</template>
