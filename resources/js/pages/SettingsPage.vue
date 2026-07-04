<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import api from '@/api/client';
import { useToast } from 'primevue/usetoast';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ToggleSwitch from 'primevue/toggleswitch';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';

const toast = useToast();
const settings = ref({});
const system = ref(null);
const loading = ref(false);
const statusLoading = ref(false);
let pollTimer = null;

const modeOptions = [
    { label: 'Least Queue', value: 'least_queue' },
    { label: 'Round Robin', value: 'round_robin' },
    { label: 'Fixed Sender', value: 'fixed' },
    { label: 'Priority', value: 'priority' },
];

onMounted(async () => {
    const { data } = await api.get('/settings');
    settings.value = data;
    await fetchSystemStatus();
    pollTimer = setInterval(fetchSystemStatus, 15000);
});

onUnmounted(() => {
    if (pollTimer) {
        clearInterval(pollTimer);
    }
});

async function fetchSystemStatus() {
    statusLoading.value = true;
    try {
        const { data } = await api.get('/system/status');
        system.value = data;
    } finally {
        statusLoading.value = false;
    }
}

async function save() {
    loading.value = true;
    try {
        const { data } = await api.put('/settings', settings.value);
        settings.value = data;
        toast.add({ severity: 'success', summary: 'تم حفظ الإعدادات', life: 3000 });
    } finally {
        loading.value = false;
    }
}

function statusSeverity(alive) {
    return alive ? 'success' : 'danger';
}
</script>

<template>
    <div class="space-y-6 max-w-3xl">
        <div>
            <h1 class="text-2xl font-bold">الإعدادات</h1>
            <p class="text-sm text-slate-500 mt-1">تكوين الطابور، التوزيع، ومراقبة المعالجة الخلفية</p>
        </div>

        <Card>
            <template #title>
                <div class="flex items-center justify-between gap-3">
                    <span>حالة المعالجة الخلفية</span>
                    <Button
                        icon="pi pi-refresh"
                        text
                        rounded
                        :loading="statusLoading"
                        @click="fetchSystemStatus"
                    />
                </div>
            </template>
            <template #content>
                <div v-if="system" class="space-y-4">
                    <div
                        class="rounded-lg px-4 py-3 flex items-start justify-between gap-3"
                        :class="system.overall.alive ? 'bg-emerald-50 dark:bg-emerald-950/30' : 'bg-red-50 dark:bg-red-950/30'"
                    >
                        <div>
                            <div class="font-semibold">الحالة العامة: {{ system.overall.label }}</div>
                            <div class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ system.overall.hint }}</div>
                        </div>
                        <Tag :value="system.overall.label" :severity="statusSeverity(system.overall.alive)" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                        <div class="rounded-lg border border-slate-200 dark:border-slate-700 p-3">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-medium">الجدولة (Cron)</span>
                                <Tag :value="system.scheduler.label" :severity="statusSeverity(system.scheduler.alive)" />
                            </div>
                            <div class="text-xs text-slate-500">
                                {{ system.scheduler.last_seen_human ? `آخر نشاط: ${system.scheduler.last_seen_human}` : 'لم يعمل بعد' }}
                            </div>
                        </div>

                        <div class="rounded-lg border border-slate-200 dark:border-slate-700 p-3">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-medium">معالجة الإرسال</span>
                                <Tag :value="system.sender_worker.label" :severity="statusSeverity(system.sender_worker.alive)" />
                            </div>
                            <div class="text-xs text-slate-500">
                                {{ system.sender_worker.last_seen_human ? `آخر نشاط: ${system.sender_worker.last_seen_human}` : 'لم يعمل بعد — راجع Cron' }}
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-center text-sm">
                        <div class="rounded-lg bg-slate-50 dark:bg-slate-800/50 p-3">
                            <div class="text-xl font-bold text-amber-600">{{ system.queue.pending }}</div>
                            <div class="text-xs text-slate-500">معلق</div>
                        </div>
                        <div class="rounded-lg bg-slate-50 dark:bg-slate-800/50 p-3">
                            <div class="text-xl font-bold text-blue-600">{{ system.queue.queue_size }}</div>
                            <div class="text-xs text-slate-500">بالطابور</div>
                        </div>
                        <div class="rounded-lg bg-slate-50 dark:bg-slate-800/50 p-3">
                            <div class="text-xl font-bold text-emerald-600">{{ system.queue.sent_today }}</div>
                            <div class="text-xs text-slate-500">مرسل اليوم</div>
                        </div>
                        <div class="rounded-lg bg-slate-50 dark:bg-slate-800/50 p-3">
                            <div class="text-xl font-bold text-red-600">{{ system.queue.failed }}</div>
                            <div class="text-xs text-slate-500">فشل</div>
                        </div>
                    </div>

                    <div v-if="system.last_sent_human" class="text-xs text-slate-500">
                        آخر رسالة ناجحة: {{ system.last_sent_human }}
                    </div>

                    <div class="rounded-lg bg-slate-50 dark:bg-slate-800/50 p-3 text-xs font-mono text-slate-600 dark:text-slate-400 space-y-1" dir="ltr">
                        <div>* * * * * php /home/intellij/public_html/wa/artisan schedule:run</div>
                        <div>* * * * * php /home/intellij/public_html/wa/artisan wa:queue-work</div>
                    </div>
                </div>
            </template>
        </Card>

        <Card>
            <template #title>الطابور العام</template>
            <template #content>
                <div class="space-y-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-medium">تفعيل الطابور</div>
                            <div class="text-xs text-slate-500">إيقاف مؤقت لاستقبال وإرسال الرسائل</div>
                        </div>
                        <ToggleSwitch v-model="settings.queue_enabled" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm text-slate-500">التأخير الافتراضي (ث)</label>
                            <InputNumber v-model="settings.default_delay_seconds" class="w-full" :min="1" />
                        </div>
                        <div>
                            <label class="text-sm text-slate-500">عدد المحاولات</label>
                            <InputNumber v-model="settings.max_retry" class="w-full" :min="0" :max="10" />
                        </div>
                        <div>
                            <label class="text-sm text-slate-500">تأخير إعادة المحاولة (ث)</label>
                            <InputNumber v-model="settings.retry_delay_seconds" class="w-full" :min="1" />
                        </div>
                        <div>
                            <label class="text-sm text-slate-500">وضع التوزيع</label>
                            <Select v-model="settings.load_balancing_mode" :options="modeOptions" option-label="label" option-value="value" class="w-full" />
                        </div>
                    </div>

                    <div class="space-y-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <span>توزيع تلقائي (Load Balancing)</span>
                            <ToggleSwitch v-model="settings.round_robin_enabled" />
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Failover تلقائي عند توقف مرسل</span>
                            <ToggleSwitch v-model="settings.automatic_failover" />
                        </div>
                        <div class="flex items-center justify-between">
                            <span>إعادة توزيع عند Offline</span>
                            <ToggleSwitch v-model="settings.offline_redistribute" />
                        </div>
                    </div>

                    <Button label="حفظ الإعدادات" :loading="loading" @click="save" />
                </div>
            </template>
        </Card>
    </div>
</template>
