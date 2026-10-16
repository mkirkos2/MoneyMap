<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    amount: number | string;
    type?: 'income' | 'expense' | 'neutral';
    currency?: string;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'neutral',
    currency: 'EUR',
});

const formattedAmount = computed(() => {
    const amount =
        typeof props.amount === 'string'
            ? parseFloat(props.amount)
            : props.amount;

    return new Intl.NumberFormat('el-GR', {
        style: 'currency',
        currency: props.currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number.isFinite(amount) ? amount : 0);
});

const amountClass = computed(() => {
    if (props.type === 'income') {
        return 'text-green-600 dark:text-green-400';
    }

    if (props.type === 'expense') {
        return 'text-red-600 dark:text-red-400';
    }

    return 'text-gray-900 dark:text-gray-100';
});
</script>

<template>
    <span :class="['font-medium tabular-nums', amountClass]">
        {{ formattedAmount }}
    </span>
</template>