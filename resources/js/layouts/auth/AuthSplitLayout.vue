<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <main class="min-h-svh overflow-hidden bg-finance-bg text-finance-text lg:grid lg:grid-cols-[0.9fr_1.1fr]">
        <section class="relative flex min-h-svh items-center justify-center border-b border-finance-border px-6 py-9 sm:px-10 lg:border-r lg:border-b-0 lg:px-12 xl:px-20">
            <div class="absolute inset-x-0 top-0 h-px bg-finance-hover" aria-hidden="true" />

            <div class="w-full max-w-[420px]">
                <Link :href="home()" class="mb-12 inline-flex items-center gap-3" aria-label="MoneyMap home">
                    <span class="flex size-9 items-center justify-center rounded-md border border-finance-accent bg-finance-accent-soft text-finance-accent">
                        <AppLogoIcon class="size-[19px] fill-current" />
                    </span>
                    <span class="text-[17px] font-semibold tracking-[-0.03em] text-finance-text">MoneyMap</span>
                </Link>

                <div class="mb-8 space-y-2.5">
                    <p class="text-[11px] font-semibold tracking-[0.16em] text-finance-accent">PERSONAL FINANCE</p>
                    <h1 v-if="title" class="text-[29px] leading-tight font-semibold tracking-[-0.035em] text-finance-text sm:text-[32px]">
                        {{ title }}
                    </h1>
                    <p v-if="description" class="max-w-sm text-sm leading-6 text-finance-muted">
                        {{ description }}
                    </p>
                </div>

                <slot />

                <p class="mt-8 text-xs text-finance-faint">A clearer view of your money.</p>
            </div>
        </section>

        <aside class="relative hidden min-h-svh items-center justify-center overflow-hidden bg-finance-surface-muted px-8 py-10 lg:flex xl:px-14">
            <div class="pointer-events-none absolute inset-0 opacity-[0.045]" aria-hidden="true">
                <div class="absolute inset-0 bg-[linear-gradient(to_right,rgba(148,163,184,0.5)_1px,transparent_1px),linear-gradient(to_bottom,rgba(148,163,184,0.5)_1px,transparent_1px)] bg-size-[36px_36px]" />
            </div>

            <div class="relative w-full max-w-[680px]">
                <div class="mb-5 flex items-center justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-semibold tracking-[0.16em] text-finance-subtle">MONEYMAP / OVERVIEW</p>
                        <p class="mt-1 text-sm text-finance-body">Your finances, at a glance</p>
                    </div>
                    <span class="shrink-0 border border-finance-border-strong px-2.5 py-1 text-[10px] font-medium tracking-wide text-finance-muted">
                        ILLUSTRATIVE DATA
                    </span>
                </div>

                <div class="overflow-hidden border border-finance-border-strong bg-finance-surface">
                    <div class="flex items-start justify-between gap-5 border-b border-finance-border px-5 py-5 sm:px-7 sm:py-6">
                        <div>
                            <p class="text-xs text-finance-muted">Total balance</p>
                            <p class="mt-2 text-[30px] leading-none font-medium tracking-[-0.04em] text-finance-text sm:text-[34px]">€12,480.00</p>
                            <p class="mt-2 text-[11px] text-finance-subtle">Example balance · not connected to an account</p>
                        </div>
                        <div class="border border-finance-border px-3 py-2 text-right">
                            <p class="text-[10px] tracking-wide text-finance-subtle">PERIOD</p>
                            <p class="mt-1 text-xs text-finance-text">This month</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 border-b border-finance-border sm:grid-cols-3">
                        <div class="border-r border-finance-border px-5 py-4 sm:px-7">
                            <p class="text-[11px] text-finance-subtle">Income</p>
                            <p class="mt-1.5 text-sm font-medium text-finance-accent">€4,250.00</p>
                        </div>
                        <div class="px-5 py-4 sm:border-r sm:border-finance-border sm:px-7">
                            <p class="text-[11px] text-finance-subtle">Expenses</p>
                            <p class="mt-1.5 text-sm font-medium text-finance-text">€2,180.00</p>
                        </div>
                        <div class="col-span-2 border-t border-finance-border px-5 py-4 sm:col-span-1 sm:border-t-0 sm:px-7">
                            <p class="text-[11px] text-finance-subtle">Net cash flow</p>
                            <p class="mt-1.5 text-sm font-medium text-finance-text">€2,070.00</p>
                        </div>
                    </div>

                    <div class="grid lg:grid-cols-[1.35fr_0.85fr]">
                        <section class="border-b border-finance-border px-5 py-5 sm:px-7 lg:border-r lg:border-b-0">
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium text-finance-text">Cash flow</p>
                                    <p class="mt-1 text-[10px] text-finance-subtle">Monthly overview · sample</p>
                                </div>
                                <span class="text-[10px] text-finance-subtle">JAN — JUN</span>
                            </div>

                            <div class="relative h-[138px]">
                                <div class="absolute inset-0 flex flex-col justify-between" aria-hidden="true">
                                    <span class="border-t border-dashed border-finance-border-soft" />
                                    <span class="border-t border-dashed border-finance-border-soft" />
                                    <span class="border-t border-dashed border-finance-border-soft" />
                                    <span class="border-t border-dashed border-finance-border-soft" />
                                </div>
                                <svg class="absolute inset-0 size-full overflow-visible" viewBox="0 0 420 138" preserveAspectRatio="none" role="img" aria-label="Illustrative monthly cash flow chart">
                                    <path d="M0 103C32 99 42 84 76 88C110 92 124 62 159 71C195 80 212 48 245 57C277 66 291 37 326 46C359 54 383 27 420 31" fill="none" stroke="var(--finance-accent)" stroke-width="2" />
                                    <path d="M0 121C32 117 48 110 76 111C109 112 126 95 159 100C191 105 211 81 245 89C278 97 293 75 326 82C362 89 385 68 420 72" fill="none" stroke="var(--finance-chart-muted)" stroke-opacity=".75" stroke-width="1.5" />
                                    <circle cx="420" cy="31" r="3.5" fill="var(--finance-accent)" />
                                </svg>
                            </div>
                            <div class="mt-2 flex justify-between text-[9px] tracking-wide text-finance-faint" aria-hidden="true">
                                <span>JAN</span><span>FEB</span><span>MAR</span><span>APR</span><span>MAY</span><span>JUN</span>
                            </div>
                            <div class="mt-3 flex gap-4 text-[10px] text-finance-subtle">
                                <span class="flex items-center gap-1.5"><i class="size-1.5 rounded-full bg-finance-action" /> Income</span>
                                <span class="flex items-center gap-1.5"><i class="size-1.5 rounded-full bg-finance-chart-muted" /> Expenses</span>
                            </div>
                        </section>

                        <section class="px-5 py-5 sm:px-7">
                            <div class="mb-4">
                                <p class="text-xs font-medium text-finance-text">Spending categories</p>
                                <p class="mt-1 text-[10px] text-finance-subtle">Example breakdown</p>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <div class="mb-1.5 flex justify-between text-[10px]"><span class="text-finance-muted">Housing</span><span class="text-finance-body">€920</span></div>
                                    <div class="h-1 bg-finance-border-soft"><div class="h-full w-[68%] bg-finance-action/80" /></div>
                                </div>
                                <div>
                                    <div class="mb-1.5 flex justify-between text-[10px]"><span class="text-finance-muted">Food</span><span class="text-finance-body">€540</span></div>
                                    <div class="h-1 bg-finance-border-soft"><div class="h-full w-[43%] bg-sky-300/70" /></div>
                                </div>
                                <div>
                                    <div class="mb-1.5 flex justify-between text-[10px]"><span class="text-finance-muted">Transport</span><span class="text-finance-body">€310</span></div>
                                    <div class="h-1 bg-finance-border-soft"><div class="h-full w-[27%] bg-violet-300/70" /></div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <section class="border-t border-finance-border">
                        <div class="flex items-center justify-between px-5 py-3 sm:px-7">
                            <p class="text-xs font-medium text-finance-text">Recent activity</p>
                            <span class="text-[10px] text-finance-subtle">SAMPLE TRANSACTIONS</span>
                        </div>
                        <div class="grid grid-cols-[1fr_auto] items-center gap-x-3 border-t border-finance-border-soft px-5 py-3 sm:grid-cols-[1fr_100px_90px] sm:px-7">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex size-7 shrink-0 items-center justify-center border border-finance-border text-[10px] text-finance-body">H</span>
                                <div class="min-w-0"><p class="truncate text-[11px] text-finance-text">Monthly rent</p><p class="mt-0.5 text-[9px] text-finance-subtle">Housing</p></div>
                            </div>
                            <span class="hidden text-[10px] text-finance-subtle sm:block">Jun 04</span>
                            <span class="text-right text-[11px] text-finance-body">−€920.00</span>
                        </div>
                        <div class="grid grid-cols-[1fr_auto] items-center gap-x-3 border-t border-finance-border-soft px-5 py-3 sm:grid-cols-[1fr_100px_90px] sm:px-7">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex size-7 shrink-0 items-center justify-center border border-finance-accent text-[10px] text-finance-accent">S</span>
                                <div class="min-w-0"><p class="truncate text-[11px] text-finance-text">Salary</p><p class="mt-0.5 text-[9px] text-finance-subtle">Income</p></div>
                            </div>
                            <span class="hidden text-[10px] text-finance-subtle sm:block">Jun 01</span>
                            <span class="text-right text-[11px] text-finance-accent">+€4,250.00</span>
                        </div>
                    </section>
                </div>

                <p class="mt-3 text-right text-[10px] text-finance-faint">All figures shown are fictional examples.</p>
            </div>
        </aside>
    </main>
</template>
