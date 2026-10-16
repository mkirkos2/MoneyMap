<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, PiggyBank } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { index as goalsIndex, store as storeGoal, update as updateGoal } from '@/routes/savings-goals';
import type { SavingsGoal } from '@/types';

const props = defineProps<{ goal?: Pick<SavingsGoal, 'id' | 'name' | 'target_amount' | 'target_date' | 'description'>; today: string }>();
const isEditing = computed(() => props.goal !== undefined);
const form = useForm({
    name: props.goal?.name ?? '',
    target_amount: props.goal?.target_amount ?? '',
    target_date: props.goal?.target_date ?? '',
    description: props.goal?.description ?? '',
});

const submit = (): void => {
    if (props.goal) {
        form.put(updateGoal.url({ savings_goal: props.goal.id }));
        return;
    }

    form.post(storeGoal.url());
};

</script>

<template>
    <Head :title="isEditing ? 'Edit savings goal' : 'Create savings goal'" />
    <main class="mx-auto flex w-full min-w-0 max-w-3xl flex-1 flex-col gap-5 px-4 py-6 sm:gap-6 sm:px-6 lg:py-8">
        <header>
            <p class="text-xs font-semibold tracking-[0.14em] text-finance-accent">PERSONAL FINANCE</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-finance-text sm:text-[28px]">{{ isEditing ? 'Edit savings goal' : 'Create savings goal' }}</h1>
            <p class="mt-1 text-sm text-finance-muted">Track money you set aside toward something that matters to you.</p>
        </header>

        <Card class="min-w-0 gap-0 rounded-lg border-finance-border bg-finance-surface py-0 shadow-none">
            <CardHeader class="border-b border-finance-border px-5 py-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <span class="flex size-10 items-center justify-center border border-finance-border bg-finance-tint text-finance-accent"><PiggyBank class="size-5" /></span>
                    <div class="grid gap-1"><CardTitle class="text-sm font-semibold text-finance-text">Goal details</CardTitle><CardDescription class="text-xs text-finance-subtle">Your progress is based on movements you record in MoneyMap.</CardDescription></div>
                </div>
            </CardHeader>
            <CardContent class="p-5 sm:p-6">
                <form class="grid gap-5" @submit.prevent="submit">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="space-y-2 sm:col-span-2">
                            <Label for="name" class="text-xs font-medium text-finance-muted">Goal name</Label>
                            <Input id="name" v-model="form.name" maxlength="120" required placeholder="For example, New bicycle" class="h-10 border-finance-border-strong bg-finance-input text-finance-text placeholder:text-finance-faint" />
                            <InputError :message="form.errors.name" />
                        </div>
                        <div class="space-y-2">
                            <Label for="target_amount" class="text-xs font-medium text-finance-muted">Target amount</Label>
                            <div class="relative"><span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-finance-subtle">€</span><Input id="target_amount" v-model="form.target_amount" type="number" min="0.01" max="9999999999.99" step="0.01" required placeholder="0.00" class="h-10 border-finance-border-strong bg-finance-input pl-8 text-finance-text placeholder:text-finance-faint" /></div>
                            <InputError :message="form.errors.target_amount" />
                        </div>
                        <div class="space-y-2">
                            <Label for="target_date" class="text-xs font-medium text-finance-muted">Target date <span class="font-normal text-finance-subtle">(optional)</span></Label>
                            <Input id="target_date" v-model="form.target_date" type="date" :min="goal ? undefined : today" class="h-10 border-finance-border-strong bg-finance-input text-finance-text" />
                            <InputError :message="form.errors.target_date" />
                        </div>
                        <div class="space-y-2 sm:col-span-2">
                            <Label for="description" class="text-xs font-medium text-finance-muted">Description <span class="font-normal text-finance-subtle">(optional)</span></Label>
                            <Textarea id="description" v-model="form.description" maxlength="5000" rows="4" placeholder="Add a little context for this goal" class="border-finance-border-strong bg-finance-input text-finance-text placeholder:text-finance-faint" />
                            <InputError :message="form.errors.description" />
                        </div>
                    </div>
                    <p class="border-l-2 border-finance-border-strong pl-3 text-xs leading-5 text-finance-subtle">This is a personal tracking tool. Recorded amounts are not a verified bank balance and do not move money automatically.</p>
                    <div class="flex flex-col-reverse gap-2 border-t border-finance-border pt-5 sm:flex-row sm:justify-end">
                        <Button variant="outline" class="h-10 border-finance-border-strong bg-transparent text-finance-body hover:bg-finance-hover hover:text-finance-text" as-child><Link :href="goalsIndex.url()"><ArrowLeft class="size-4" />Cancel</Link></Button>
                        <Button type="submit" class="h-10 bg-finance-action px-4 font-semibold text-finance-action-text hover:bg-finance-action-hover" :disabled="form.processing">{{ form.processing ? 'Saving…' : isEditing ? 'Save changes' : 'Create goal' }}</Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </main>
</template>
