<script setup>
import { onMounted, onUnmounted } from 'vue';
import { useDashboardStore } from '@/stores/dashboard';
import { useSenderStore } from '@/stores/sender';
import { useToast } from 'primevue/usetoast';
import StatCard from '@/components/dashboard/StatCard.vue';
import SenderCard from '@/components/senders/SenderCard.vue';
import Skeleton from 'primevue/skeleton';

const dashboard = useDashboardStore();
const senderStore = useSenderStore();
const toast = useToast();

onMounted(() => dashboard.startPolling(10000));
onUnmounted(() => dashboard.stopPolling());

async function onCheck(id) {
    await senderStore.checkStatus(id);
    toast.add({ severity: 'info', summary: 'تم فحص الاتصال', life: 3000 });
    dashboard.fetchDashboard();
}

async function onToggle(id) {
    await senderStore.toggle(id);
    dashboard.fetchDashboard();
}

async function onRedistribute(id) {
    const result = await senderStore.redistribute(id);
    toast.add({ severity: 'success', summary: result.message, life: 4000 });
    dashboard.fetchDashboard();
}
</script>

<template>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold">لوحة القيادة</h1>
            <p class="text-sm text-slate-500 mt-1">مراقبة حية لطابور WhatsApp والمرسلين</p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <StatCard label="معلق" :value="dashboard.stats?.pending" icon="pi pi-clock" color="amber" :loading="dashboard.loading" />
            <StatCard label="معيّن" :value="dashboard.stats?.assigned" icon="pi pi-user" color="blue" :loading="dashboard.loading" />
            <StatCard label="جاري الإرسال" :value="dashboard.stats?.sending" icon="pi pi-send" color="emerald" :loading="dashboard.loading" />
            <StatCard label="مرسل اليوم" :value="dashboard.stats?.sent_today" icon="pi pi-check" color="emerald" :loading="dashboard.loading" />
            <StatCard label="فشل" :value="dashboard.stats?.failed" icon="pi pi-times" color="red" :loading="dashboard.loading" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <StatCard label="حجم الطابور" :value="dashboard.stats?.queue_size" icon="pi pi-database" color="slate" :loading="dashboard.loading" />
            <StatCard label="متوسط الإرسال" :value="dashboard.stats?.avg_send_time_ms ? `${dashboard.stats.avg_send_time_ms} ms` : '—'" icon="pi pi-stopwatch" color="blue" :loading="dashboard.loading" />
            <StatCard label="نسبة النجاح" :value="dashboard.stats ? `${dashboard.stats.success_rate}%` : '—'" icon="pi pi-percentage" color="emerald" :loading="dashboard.loading" />
        </div>

        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">مراقبة المرسلين</h2>
                <span v-if="dashboard.lastUpdated" class="text-xs text-slate-500">
                    آخر تحديث: {{ dashboard.lastUpdated.toLocaleTimeString('ar') }}
                </span>
            </div>

            <div v-if="dashboard.loading && !dashboard.senders.length" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Skeleton v-for="i in 3" :key="i" height="220px" class="rounded-xl" />
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <SenderCard
                    v-for="sender in dashboard.senders"
                    :key="sender.id"
                    :sender="sender"
                    @check="onCheck"
                    @toggle="onToggle"
                    @redistribute="onRedistribute"
                />
            </div>
        </section>
    </div>
</template>
