<script setup>
import { onMounted, computed } from 'vue';
import { useDashboardStore } from '@/stores/dashboard';
import Card from 'primevue/card';
import Chart from 'primevue/chart';

const dashboard = useDashboardStore();

onMounted(async () => {
    await dashboard.fetchAnalytics();
});

const sourceChart = computed(() => {
    const items = dashboard.analytics?.messages_by_source ?? [];
    return {
        labels: items.map((i) => i.source),
        datasets: [{
            data: items.map((i) => i.total),
            backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#64748b'],
        }],
    };
});

const dayChart = computed(() => {
    const items = dashboard.analytics?.messages_by_day ?? [];
    return {
        labels: items.map((i) => i.day),
        datasets: [{
            label: 'الرسائل المرسلة',
            data: items.map((i) => i.total),
            borderColor: '#10b981',
            tension: 0.3,
        }],
    };
});
</script>

<template>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold">التحليلات</h1>
            <p class="text-sm text-slate-500 mt-1">إحصائيات الأداء حسب المرسل والمصدر والوقت</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <Card>
                <template #title>الرسائل حسب المصدر</template>
                <template #content>
                    <Chart type="doughnut" :data="sourceChart" class="max-h-72" />
                </template>
            </Card>
            <Card>
                <template #title>الرسائل حسب اليوم</template>
                <template #content>
                    <Chart type="line" :data="dayChart" class="max-h-72" />
                </template>
            </Card>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Card>
                <template #title>أفضل المرسلين</template>
                <template #content>
                    <ul class="space-y-2 text-sm">
                        <li v-for="item in dashboard.analytics?.top_active_senders ?? []" :key="item.sender" class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                            <span>{{ item.sender }}</span>
                            <span class="font-semibold">{{ item.total }}</span>
                        </li>
                    </ul>
                </template>
            </Card>
            <Card>
                <template #title>نسبة النجاح لكل مرسل</template>
                <template #content>
                    <ul class="space-y-2 text-sm">
                        <li v-for="item in dashboard.analytics?.success_rate_per_sender ?? []" :key="item.sender" class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                            <span>{{ item.sender }}</span>
                            <span class="font-semibold text-emerald-600">{{ item.rate }}%</span>
                        </li>
                    </ul>
                </template>
            </Card>
        </div>
    </div>
</template>
