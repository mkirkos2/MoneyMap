<script setup lang="ts">
import SavingsGoalForm from '@/components/SavingsGoalForm.vue';
import { create as createGoal, index as goalsIndex } from '@/routes/savings-goals';

defineProps<{ today: string }>();
defineOptions({ layout: { breadcrumbs: [{ title: 'Savings Goals', href: goalsIndex.url() }, { title: 'Create goal', href: createGoal.url() }] } });
</script>

<template><SavingsGoalForm :today="today" /></template>
