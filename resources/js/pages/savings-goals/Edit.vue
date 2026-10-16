<script setup lang="ts">
import SavingsGoalForm from '@/components/SavingsGoalForm.vue';
import { index as goalsIndex } from '@/routes/savings-goals';
import type { SavingsGoal } from '@/types';

defineProps<{ goal: Pick<SavingsGoal, 'id' | 'name' | 'target_amount' | 'target_date' | 'description'>; today: string }>();
defineOptions({ layout: { breadcrumbs: [{ title: 'Savings Goals', href: goalsIndex.url() }, { title: 'Edit goal', href: goalsIndex.url() }] } });
</script>

<template><SavingsGoalForm :goal="goal" :today="today" /></template>
