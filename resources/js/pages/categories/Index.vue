<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ArrowDownRight, ArrowLeft, ArrowRight, ArrowUpRight, Edit, FolderOpen, Plus, Tag, Trash2 } from '@lucide/vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { create as createCategory, destroy as destroyCategory, edit, index as categoriesRoute } from '@/routes/categories';
import type { Category, PaginatedResponse } from '@/types';

interface Props {
    categories: PaginatedResponse<Category> & {
        current_page: number;
        last_page: number;
        from: number | null;
        to: number | null;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: {
        type?: string;
    };
}

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Categories',
                href: categoriesRoute.url(),
            },
        ],
    },
});

const categoryGroups = computed(() => [
    {
        type: 'income' as const,
        title: 'Income categories',
        description: 'Categories used to organize money coming in.',
        categories: props.categories.data.filter((category) => category.type === 'income'),
    },
    {
        type: 'expense' as const,
        title: 'Expense categories',
        description: 'Categories used to organize money going out.',
        categories: props.categories.data.filter((category) => category.type === 'expense'),
    },
].filter((group) => group.categories.length > 0));

const handleDelete = (categoryId: number): void => {
    router.delete(destroyCategory.url({ category: categoryId }));
};
</script>

<template>
    <Head title="Categories" />

    <main class="mx-auto flex w-full min-w-0 max-w-[1600px] flex-1 flex-col gap-5 px-4 py-6 sm:gap-6 sm:px-6 lg:px-8 lg:py-8">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold tracking-[0.14em] text-finance-accent">PERSONAL FINANCE</p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-finance-text sm:text-[28px]">Categories</h1>
                <p class="mt-1 text-sm text-finance-muted">Organize income and expenses around the way you manage money.</p>
            </div>

            <Button as-child class="h-10 shrink-0 rounded-md bg-finance-action px-4 font-semibold text-finance-action-text hover:bg-finance-action-hover">
                <Link :href="createCategory.url()">
                    <Plus class="size-4" />
                    Add category
                </Link>
            </Button>
        </header>

        <Card class="min-w-0 gap-0 rounded-lg border-finance-border bg-finance-surface py-0 shadow-none">
            <CardHeader class="flex flex-col gap-2 border-b border-finance-border px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div class="grid gap-1">
                    <CardTitle class="text-sm font-semibold text-finance-text">Your categories</CardTitle>
                    <CardDescription class="text-xs text-finance-subtle">
                        {{ categories.total }} {{ categories.total === 1 ? 'category' : 'categories' }} in your list
                    </CardDescription>
                </div>
                <p v-if="categories.total > 0" class="text-xs tabular-nums text-finance-subtle">
                    Showing {{ categories.from }}–{{ categories.to }} of {{ categories.total }}
                </p>
            </CardHeader>

            <template v-if="categories.data.length > 0">
                <div
                    class="grid min-w-0 gap-4 p-4 sm:p-5 xl:gap-5 xl:p-6"
                    :class="categoryGroups.length > 1 ? 'xl:grid-cols-2' : 'xl:max-w-3xl xl:grid-cols-1'"
                >
                    <Card
                        v-for="group in categoryGroups"
                        :key="group.type"
                        class="min-w-0 gap-0 rounded-lg border-finance-border-soft bg-finance-surface-muted py-0 shadow-none"
                    >
                        <CardHeader class="flex flex-row items-start gap-3 border-b border-finance-border-soft px-4 py-4 sm:px-5">
                            <span
                                class="flex size-9 shrink-0 items-center justify-center border"
                                :class="group.type === 'income'
                                    ? 'border-finance-accent bg-finance-action/[0.07] text-finance-accent'
                                    : 'border-finance-negative-border bg-finance-negative-soft text-finance-negative'"
                            >
                                <ArrowDownRight v-if="group.type === 'income'" class="size-4" />
                                <ArrowUpRight v-else class="size-4" />
                            </span>
                            <div class="grid min-w-0 gap-1">
                                <CardTitle class="text-sm font-semibold text-finance-text">{{ group.title }}</CardTitle>
                                <CardDescription class="text-xs leading-5 text-finance-subtle">{{ group.description }}</CardDescription>
                            </div>
                        </CardHeader>

                        <CardContent class="divide-y divide-finance-border-soft px-4 py-0 sm:px-5">
                            <article
                                v-for="category in group.categories"
                                :key="category.id"
                                class="flex min-w-0 items-center justify-between gap-3 py-4 first:pt-4 last:pb-4"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="relative flex size-10 shrink-0 items-center justify-center border border-finance-border bg-finance-tint text-finance-body">
                                        <Tag v-if="!category.icon" class="size-4" />
                                        <span v-else class="max-w-9 truncate px-1 text-[9px] font-semibold tracking-wide" :title="category.icon">
                                            {{ category.icon }}
                                        </span>
                                        <span
                                            class="absolute right-1 bottom-1 size-1.5 rounded-full ring-2 ring-finance-surface-muted"
                                            :style="{ backgroundColor: category.color || '#64748b' }"
                                            aria-hidden="true"
                                        />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="break-words text-sm font-medium text-finance-text">{{ category.name }}</p>
                                        <p
                                            class="mt-1 text-[10px] font-semibold tracking-[0.12em]"
                                            :class="category.type === 'income' ? 'text-finance-accent' : 'text-finance-negative'"
                                        >
                                            {{ category.type === 'income' ? 'INCOME' : 'EXPENSE' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    <Button as-child variant="outline" size="sm" class="size-9 border-finance-border-strong bg-transparent p-0 text-finance-body hover:bg-finance-hover hover:text-finance-text">
                                        <Link :href="edit.url({ category: category.id })" :aria-label="`Edit ${category.name}`">
                                            <Edit class="size-4" />
                                        </Link>
                                    </Button>
                                    <ConfirmDialog
                                        title="Delete category"
                                        description="Are you sure you want to delete this category? This action cannot be undone."
                                        variant="destructive"
                                        confirm-text="Delete category"
                                        @confirm="handleDelete(category.id)"
                                    >
                                        <template #trigger>
                                            <Button variant="outline" size="sm" class="size-9 border-finance-border-strong bg-transparent p-0 text-finance-muted hover:border-finance-negative-border hover:bg-finance-negative-soft hover:text-finance-negative" :aria-label="`Delete ${category.name}`">
                                                <Trash2 class="size-4" />
                                            </Button>
                                        </template>
                                    </ConfirmDialog>
                                </div>
                            </article>
                        </CardContent>
                    </Card>
                </div>

                <footer v-if="categories.last_page > 1" class="flex flex-col gap-3 border-t border-finance-border px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <p class="text-xs text-finance-subtle">
                        Showing <span class="tabular-nums text-finance-body">{{ categories.from }}–{{ categories.to }}</span>
                        of <span class="tabular-nums text-finance-body">{{ categories.total }}</span> categories
                    </p>
                    <div class="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            class="h-9 border-finance-border-strong bg-transparent px-3 text-finance-body hover:bg-finance-hover hover:text-finance-text"
                            :disabled="!categories.prev_page_url"
                            @click="router.visit(categories.prev_page_url || '')"
                        >
                            <ArrowLeft class="size-4" />
                            Previous
                        </Button>
                        <span class="px-1 text-xs tabular-nums text-finance-subtle">{{ categories.current_page }} / {{ categories.last_page }}</span>
                        <Button
                            variant="outline"
                            size="sm"
                            class="h-9 border-finance-border-strong bg-transparent px-3 text-finance-body hover:bg-finance-hover hover:text-finance-text"
                            :disabled="!categories.next_page_url"
                            @click="router.visit(categories.next_page_url || '')"
                        >
                            Next
                            <ArrowRight class="size-4" />
                        </Button>
                    </div>
                </footer>
            </template>

            <CardContent v-else class="flex flex-col items-center px-5 py-12 text-center sm:py-16">
                <span class="flex size-12 items-center justify-center border border-finance-border bg-finance-tint text-finance-muted">
                    <FolderOpen class="size-5" />
                </span>
                <p class="mt-4 text-sm font-medium text-finance-text">No categories yet</p>
                <p class="mt-1 max-w-sm text-xs leading-5 text-finance-subtle">Create an income or expense category to organize your transactions.</p>
                <Button as-child class="mt-5 h-9 rounded-md bg-finance-action px-3.5 text-xs font-semibold text-finance-action-text hover:bg-finance-action-hover">
                    <Link :href="createCategory.url()">
                        <Plus class="size-4" />
                        Add category
                    </Link>
                </Button>
            </CardContent>
        </Card>
    </main>
</template>
