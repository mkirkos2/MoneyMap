<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowDownLeft, ArrowLeft, ArrowUpRight, CalendarDays, Check, Edit, LoaderCircle, Plus, Trash2, TriangleAlert } from '@lucide/vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import InputError from '@/components/InputError.vue';
import MoneyAmount from '@/components/MoneyAmount.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { destroy as destroyGoal, edit as editGoal, index as goalsIndex } from '@/routes/savings-goals';
import { store as storeEntry } from '@/routes/savings-goals/entries';
import type { SavingsGoal, SavingsGoalEntry } from '@/types';

interface EntryPagination {
    data: SavingsGoalEntry[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
}

interface Props {
    goal: SavingsGoal;
    entries: EntryPagination;
    today: string;
}

const props = defineProps<Props>();
defineOptions({ layout: { breadcrumbs: [{ title: 'Savings Goals', href: goalsIndex.url() }] } });

type EntryType = 'contribution' | 'withdrawal';
const activeEntryType = ref<EntryType>('contribution');
const deleting = ref(false);
const entryForm = useForm({ type: 'contribution' as EntryType, amount: '', entry_date: props.today, note: '' });
const formattedTargetDate = computed(() => {
    if (!props.goal.target_date) return null;
    const [year, month, day] = props.goal.target_date.split('-').map(Number);
    return new Intl.DateTimeFormat('el-GR', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(year, month - 1, day));
});
const formatEntryDate = (date: string): string => {
    const [year, month, day] = date.split('-').map(Number);
    return new Intl.DateTimeFormat('el-GR', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(year, month - 1, day));
};

const selectEntryType = (type: EntryType): void => {
    activeEntryType.value = type;
    entryForm.type = type;
    entryForm.clearErrors();
};

const submitEntry = (): void => {
    entryForm.post(storeEntry.url({ savings_goal: props.goal.id }), {
        preserveScroll: true,
        onSuccess: () => entryForm.reset('amount', 'note'),
    });
};

const handleDelete = (): void => {
    deleting.value = true;
    router.delete(destroyGoal.url({ savings_goal: props.goal.id }), { onFinish: () => { deleting.value = false; } });
};
</script>

<template>
    <Head :title="goal.name" />
    <main class="mx-auto flex min-h-full w-full min-w-0 max-w-[1400px] flex-1 flex-col gap-5 px-4 py-6 sm:gap-6 sm:px-6 lg:px-8 lg:py-8">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex min-w-0 items-start gap-3"><Button as-child variant="outline" size="icon" class="size-9 shrink-0 border-finance-border-strong bg-transparent text-finance-body hover:bg-finance-hover hover:text-finance-text"><Link :href="goalsIndex.url()" aria-label="Back to savings goals"><ArrowLeft class="size-4" /></Link></Button><div class="min-w-0"><p class="text-xs font-semibold tracking-[0.14em] text-finance-accent">SAVINGS GOAL</p><h1 class="mt-2 break-words text-2xl font-semibold tracking-tight text-finance-text sm:text-[28px]">{{ goal.name }}</h1><p v-if="goal.description" class="mt-1 max-w-2xl whitespace-pre-line text-sm text-finance-muted">{{ goal.description }}</p><p class="mt-2 flex items-center gap-1.5 text-xs text-finance-subtle"><CalendarDays class="size-3.5" />{{ goal.target_date ? `Target date ${formattedTargetDate}` : 'No target date' }}</p></div></div>
            <div class="flex shrink-0 gap-2 pl-12 sm:pl-0"><Button as-child variant="outline" class="h-9 border-finance-border-strong bg-transparent px-3 text-finance-body hover:bg-finance-hover hover:text-finance-text"><Link :href="editGoal.url({ savings_goal: goal.id })"><Edit class="size-4" />Edit</Link></Button><ConfirmDialog title="Archive savings goal" description="Archive this goal? Its movement history will be retained and it will no longer appear in your active goals." variant="destructive" confirm-text="Archive goal" @confirm="handleDelete"><template #trigger><Button variant="outline" class="h-9 border-finance-border-strong bg-transparent px-3 text-finance-muted hover:border-finance-negative-border hover:bg-finance-negative-soft hover:text-finance-negative" :disabled="deleting"><LoaderCircle v-if="deleting" class="size-4 animate-spin" /><Trash2 v-else class="size-4" />Archive</Button></template></ConfirmDialog></div>
        </header>

        <p class="border-l-2 border-finance-border-strong pl-3 text-xs leading-5 text-finance-subtle">Amounts reflect movements you have recorded. They are not a verified bank balance and MoneyMap does not move money automatically.</p>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Goal summary">
            <Card class="gap-0 rounded-lg border-finance-border bg-finance-surface py-0 shadow-none"><CardContent class="p-4"><p class="text-[10px] font-semibold tracking-[0.12em] text-finance-subtle">RECORDED AMOUNT</p><MoneyAmount class="mt-2 block text-xl" :amount="goal.current_amount" /><p class="mt-1 text-xs text-finance-subtle">All contributions less withdrawals</p></CardContent></Card>
            <Card class="gap-0 rounded-lg border-finance-border bg-finance-surface py-0 shadow-none"><CardContent class="p-4"><p class="text-[10px] font-semibold tracking-[0.12em] text-finance-subtle">TARGET AMOUNT</p><MoneyAmount class="mt-2 block text-xl text-finance-body" :amount="goal.target_amount" /></CardContent></Card>
            <Card class="gap-0 rounded-lg border-finance-border bg-finance-surface py-0 shadow-none"><CardContent class="p-4"><p class="text-[10px] font-semibold tracking-[0.12em] text-finance-subtle">REMAINING TO TARGET</p><MoneyAmount class="mt-2 block text-xl text-finance-body" :amount="goal.remaining_amount" /></CardContent></Card>
            <Card class="gap-0 rounded-lg border-finance-border bg-finance-surface py-0 shadow-none"><CardContent class="p-4"><p class="text-[10px] font-semibold tracking-[0.12em] text-finance-subtle">MONTHLY PACE</p><MoneyAmount v-if="goal.monthly_required_amount !== null" class="mt-2 block text-xl text-finance-body" :amount="goal.monthly_required_amount" /><p v-else-if="goal.is_overdue" class="mt-2 flex items-center gap-1.5 text-sm font-semibold text-amber-600 dark:text-amber-400"><TriangleAlert class="size-4" />Target date passed</p><p v-else class="mt-2 text-sm text-finance-muted">Add a target date</p><p v-if="goal.months_remaining !== null" class="mt-1 text-xs text-finance-subtle">Across {{ goal.months_remaining }} calendar {{ goal.months_remaining === 1 ? 'month' : 'months' }}, including this month</p></CardContent></Card>
        </section>

        <Card class="gap-0 rounded-lg border-finance-border bg-finance-surface py-0 shadow-none"><CardHeader class="border-b border-finance-border px-5 py-4 sm:px-6"><div class="flex items-center justify-between gap-3"><div><CardTitle class="text-sm font-semibold text-finance-text">Goal progress</CardTitle><CardDescription class="mt-1 text-xs text-finance-subtle">{{ goal.is_complete ? 'Target reached' : goal.is_overdue ? 'Target date has passed' : `${goal.progress_percentage}% of your target` }}</CardDescription></div><span v-if="goal.is_complete" class="flex items-center gap-1.5 text-xs font-semibold text-finance-positive"><Check class="size-4" />Completed</span><span v-else-if="goal.is_overdue" class="flex items-center gap-1.5 text-xs font-semibold text-amber-600 dark:text-amber-400"><TriangleAlert class="size-4" />Overdue</span></div></CardHeader><CardContent class="p-5 sm:p-6"><div class="h-3 overflow-hidden rounded-full bg-finance-tint" role="progressbar" :aria-label="`${goal.name} progress`" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="goal.progress_percentage"><div class="h-full rounded-full bg-finance-action transition-[width]" :style="{ width: `${goal.progress_percentage}%` }" /></div><div class="mt-3 flex justify-between gap-3 text-xs"><span class="font-semibold tabular-nums text-finance-body">{{ goal.progress_percentage }}%</span><span class="text-right text-finance-subtle"><MoneyAmount :amount="goal.current_amount" /> recorded of <MoneyAmount :amount="goal.target_amount" /> target</span></div></CardContent></Card>

        <div class="grid min-w-0 gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(320px,0.8fr)]">
            <Card class="min-w-0 gap-0 rounded-lg border-finance-border bg-finance-surface py-0 shadow-none"><CardHeader class="border-b border-finance-border px-5 py-4 sm:px-6"><CardTitle class="text-sm font-semibold text-finance-text">Movement history</CardTitle><CardDescription class="text-xs text-finance-subtle">Entries are retained as an immutable record. Correct mistakes with a reverse movement and, if needed, a new correct entry.</CardDescription></CardHeader><div v-if="entries.data.length" class="divide-y divide-finance-border-soft"><article v-for="entry in entries.data" :key="entry.id" class="flex min-w-0 items-center justify-between gap-3 px-5 py-4 sm:px-6"><div class="flex min-w-0 items-center gap-3"><span class="flex size-9 shrink-0 items-center justify-center border border-finance-border bg-finance-tint" :class="entry.type === 'contribution' ? 'text-finance-positive' : 'text-finance-negative'"><ArrowDownLeft v-if="entry.type === 'contribution'" class="size-4" /><ArrowUpRight v-else class="size-4" /></span><div class="min-w-0"><p class="text-sm font-medium text-finance-text">{{ entry.type === 'contribution' ? 'Contribution' : 'Withdrawal' }}</p><p class="mt-1 truncate text-xs text-finance-subtle">{{ formatEntryDate(entry.entry_date) }}<span v-if="entry.note"> · {{ entry.note }}</span></p></div></div><span class="shrink-0 text-sm" :class="entry.type === 'contribution' ? 'text-finance-positive' : 'text-finance-negative'">{{ entry.type === 'contribution' ? '+' : '−' }}<MoneyAmount :amount="entry.amount" :type="entry.type === 'contribution' ? 'income' : 'expense'" /></span></article></div><CardContent v-else class="px-5 py-10 text-center"><p class="text-sm font-medium text-finance-text">No movements recorded</p><p class="mt-1 text-xs text-finance-subtle">Add your first contribution or withdrawal to start tracking progress.</p></CardContent><footer v-if="entries.last_page > 1" class="flex items-center justify-between gap-3 border-t border-finance-border px-5 py-3 sm:px-6"><span class="text-xs text-finance-subtle">{{ entries.current_page }} / {{ entries.last_page }}</span><div class="flex gap-2"><Button variant="outline" size="sm" class="border-finance-border-strong bg-transparent text-finance-body" :disabled="!entries.prev_page_url" @click="router.visit(entries.prev_page_url || '')">Previous</Button><Button variant="outline" size="sm" class="border-finance-border-strong bg-transparent text-finance-body" :disabled="!entries.next_page_url" @click="router.visit(entries.next_page_url || '')">Next</Button></div></footer></Card>

            <Card class="min-w-0 gap-0 rounded-lg border-finance-border bg-finance-surface py-0 shadow-none"><CardHeader class="border-b border-finance-border px-5 py-4 sm:px-6"><CardTitle class="text-sm font-semibold text-finance-text">Record a movement</CardTitle><CardDescription class="text-xs text-finance-subtle">Record money you set aside or take back out.</CardDescription></CardHeader><CardContent class="p-5 sm:p-6"><div class="grid grid-cols-2 gap-2"><Button type="button" variant="outline" class="h-10 border-finance-border-strong" :class="activeEntryType === 'contribution' ? 'border-finance-accent bg-finance-accent-soft text-finance-positive' : 'bg-transparent text-finance-body'" @click="selectEntryType('contribution')"><ArrowDownLeft class="size-4" />Contribution</Button><Button type="button" variant="outline" class="h-10 border-finance-border-strong" :class="activeEntryType === 'withdrawal' ? 'border-finance-negative-border bg-finance-negative-soft text-finance-negative' : 'bg-transparent text-finance-body'" @click="selectEntryType('withdrawal')"><ArrowUpRight class="size-4" />Withdrawal</Button></div><form class="mt-5 grid gap-4" @submit.prevent="submitEntry"><div class="grid gap-2"><Label for="entry-amount" class="text-xs font-medium text-finance-muted">Amount</Label><div class="relative"><span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-finance-subtle">€</span><Input id="entry-amount" v-model="entryForm.amount" type="number" min="0.01" max="9999999999.99" step="0.01" required placeholder="0.00" class="h-10 border-finance-border-strong bg-finance-input pl-8 text-finance-text" /></div><InputError :message="entryForm.errors.amount" /><p v-if="activeEntryType === 'withdrawal'" class="text-xs text-finance-subtle">Available to withdraw: <MoneyAmount :amount="goal.current_amount" /></p></div><div class="grid gap-2"><Label for="entry-date" class="text-xs font-medium text-finance-muted">Date</Label><Input id="entry-date" v-model="entryForm.entry_date" type="date" required class="h-10 border-finance-border-strong bg-finance-input text-finance-text" /><InputError :message="entryForm.errors.entry_date" /></div><div class="grid gap-2"><Label for="entry-note" class="text-xs font-medium text-finance-muted">Note <span class="font-normal text-finance-subtle">(optional)</span></Label><Textarea id="entry-note" v-model="entryForm.note" maxlength="1000" rows="3" placeholder="Add a note for your history" class="border-finance-border-strong bg-finance-input text-finance-text placeholder:text-finance-faint" /><InputError :message="entryForm.errors.note" /></div><InputError :message="entryForm.errors.type" /><Button type="submit" class="h-10 bg-finance-action font-semibold text-finance-action-text hover:bg-finance-action-hover" :disabled="entryForm.processing">{{ entryForm.processing ? 'Saving…' : activeEntryType === 'contribution' ? 'Add contribution' : 'Record withdrawal' }}</Button></form></CardContent></Card>
        </div>
    </main>
</template>
