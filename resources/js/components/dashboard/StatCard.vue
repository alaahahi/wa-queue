<script setup>
import Card from 'primevue/card';
import Skeleton from 'primevue/skeleton';

defineProps({
    label: String,
    value: [String, Number],
    icon: String,
    color: { type: String, default: 'emerald' },
    loading: Boolean,
});

const colors = {
    emerald: 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950',
    blue: 'text-blue-600 bg-blue-50 dark:bg-blue-950',
    amber: 'text-amber-600 bg-amber-50 dark:bg-amber-950',
    red: 'text-red-600 bg-red-50 dark:bg-red-950',
    slate: 'text-slate-600 bg-slate-100 dark:bg-slate-800',
};
</script>

<template>
    <Card class="!shadow-sm transition-transform duration-200 hover:-translate-y-0.5">
        <template #content>
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-xs font-medium text-slate-500 mb-1">{{ label }}</div>
                    <Skeleton v-if="loading" width="4rem" height="1.75rem" />
                    <div v-else class="text-2xl font-bold tracking-tight tabular-nums">{{ value ?? '—' }}</div>
                </div>
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" :class="colors[color]">
                    <i :class="icon"></i>
                </div>
            </div>
        </template>
    </Card>
</template>
