<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, Check, Plus, PiggyBank, TriangleAlert } from '@lucide/vue';
import MoneyAmount from '@/components/MoneyAmount.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { create as createGoal, index as goalsIndex, show as showGoal } from '@/routes/savings-goals';
import type { SavingsGoal } from '@/types';

defineProps<{ goals: SavingsGoal[] }>();
defineOptions({ layout: { breadcrumbs: [{ title: 'Savings Goals', href: goalsIndex.url() }] } });

const formatDate = (date: string): string => {
    const [year, month, day] = date.split('-').map(Number);
    return new Intl.DateTimeFormat('el-GR', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(year, month - 1, day));
};
</script>

<template>
    <Head title="Savings Goals" />
    <main class="mx-auto flex min-h-full w-full min-w-0 max-w-[1600px] flex-1 flex-col gap-5 px-4 py-6 sm:gap-6 sm:px-6 lg:px-8 lg:py-8">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0"><p class="text-xs font-semibold tracking-[0.14em] text-finance-accent">PERSONAL FINANCE</p><h1 class="mt-2 text-2xl font-semibold tracking-tight text-finance-text sm:text-[28px]">Savings Goals</h1><p class="mt-1 max-w-2xl text-sm text-finance-muted">Track the progress of money you set aside for your plans.</p></div>
            <Button as-child class="h-10 shrink-0 bg-finance-action px-4 font-semibold text-finance-action-text hover:bg-finance-action-hover"><Link :href="createGoal.url()"><Plus class="size-4" />Add goal</Link></Button>
        </header>

        <p class="border-l-2 border-finance-border-strong pl-3 text-xs leading-5 text-finance-subtle">Progress reflects only the contributions and withdrawals you record here. It is not a verified bank balance and does not move money automatically.</p>

        <section v-if="goals.length" class="grid min-w-0 gap-4 sm:grid-cols-2 xl:grid-cols-3 xl:gap-5" aria-label="Your savings goals">
            <Link v-for="goal in goals" :key="goal.id" :href="showGoal.url({ savings_goal: goal.id })" class="group min-w-0 rounded-lg border border-finance-border bg-finance-surface p-5 transition-colors hover:border-finance-border-strong focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-finance-accent">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3"><span class="flex size-10 shrink-0 items-center justify-center border border-finance-border bg-finance-tint text-finance-accent"><PiggyBank class="size-5" /></span><div class="min-w-0"><h2 class="break-words text-sm font-semibold text-finance-text group-hover:text-finance-accent">{{ goal.name }}</h2><p class="mt-1 text-xs text-finance-subtle">{{ goal.is_complete ? 'Completed' : goal.is_overdue ? 'Past target date' : goal.target_date ? `Target ${formatDate(goal.target_date)}` : 'No target date' }}</p></div></div>
                    <Check v-if="goal.is_complete" class="size-4 shrink-0 text-finance-positive" /><TriangleAlert v-else-if="goal.is_overdue" class="size-4 shrink-0 text-amber-500" />
                </div>
                <p v-if="goal.description" class="mt-4 line-clamp-2 text-xs leading-5 text-finance-muted">{{ goal.description }}</p>
                <div class="mt-5 flex items-end justify-between gap-3">
                    <div><p class="text-[10px] font-semibold tracking-[0.12em] text-finance-subtle">RECORDED AMOUNT</p><MoneyAmount class="mt-1 block text-lg" :amount="goal.current_amount" /></div>
                    <div class="text-right"><p class="text-[10px] font-semibold tracking-[0.12em] text-finance-subtle">TARGET</p><MoneyAmount class="mt-1 block text-sm text-finance-body" :amount="goal.target_amount" /></div>
                </div>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-finance-tint" role="progressbar" :aria-label="`${goal.name} progress`" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="goal.progress_percentage"><div class="h-full rounded-full bg-finance-action transition-[width]" :style="{ width: `${goal.progress_percentage}%` }" /></div>
                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs"><span class="font-semibold text-finance-body">{{ goal.progress_percentage }}% complete</span><span v-if="goal.is_complete" class="text-finance-positive">Target reached</span><span v-else-if="goal.is_overdue" class="text-amber-600 dark:text-amber-400">Past due</span><span v-else-if="goal.monthly_required_amount !== null" class="flex items-center gap-1 text-finance-subtle"><CalendarDays class="size-3.5" /><MoneyAmount :amount="goal.monthly_required_amount" /> / month</span><span v-else class="text-finance-subtle">No monthly estimate</span></div>
            </Link>
        </section>

        <Card v-else class="gap-0 rounded-lg border-finance-border bg-finance-surface py-0 shadow-none">
            <CardContent class="flex flex-col items-center px-5 py-12 text-center sm:py-16"><span class="flex size-12 items-center justify-center border border-finance-border bg-finance-tint text-finance-muted"><PiggyBank class="size-5" /></span><h2 class="mt-4 text-sm font-semibold text-finance-text">No savings goals yet</h2><p class="mt-1 max-w-sm text-xs leading-5 text-finance-subtle">Create a goal to track your recorded contributions and progress over time.</p><Button as-child class="mt-5 h-9 bg-finance-action px-3.5 text-xs font-semibold text-finance-action-text hover:bg-finance-action-hover"><Link :href="createGoal.url()"><Plus class="size-4" />Create your first goal</Link></Button></CardContent>
        </Card>
    </main>
</template>
