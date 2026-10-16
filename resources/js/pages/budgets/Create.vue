<script setup lang="ts">
import BudgetForm from '@/components/BudgetForm.vue';
import { create as createBudget, index as budgetsIndex } from '@/routes/budgets';
import type { Category } from '@/types';

defineProps<{
    categories: Category[];
    month: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Budgets', href: budgetsIndex.url() },
            { title: 'Create budget', href: createBudget.url() },
        ],
    },
});
</script>

<template>
    <BudgetForm :categories="categories" :month="month" />
</template>
