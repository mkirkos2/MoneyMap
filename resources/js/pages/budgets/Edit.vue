<script setup lang="ts">
import BudgetForm from '@/components/BudgetForm.vue';
import { index as budgetsIndex } from '@/routes/budgets';
import type { Budget, Category } from '@/types';

defineProps<{
    budget: Budget;
    categories: Category[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Budgets', href: budgetsIndex.url() },
        ],
    },
});
</script>

<template>
    <BudgetForm :budget="budget" :categories="categories" />
</template>
