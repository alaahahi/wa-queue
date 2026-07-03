<script setup>
import { ref, onMounted } from 'vue';
import api from '@/api/client';
import { useToast } from 'primevue/usetoast';
import Card from 'primevue/card';
import Button from 'primevue/button';
import ToggleSwitch from 'primevue/toggleswitch';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';

const toast = useToast();
const settings = ref({});
const loading = ref(false);

const modeOptions = [
    { label: 'Least Queue', value: 'least_queue' },
    { label: 'Round Robin', value: 'round_robin' },
    { label: 'Fixed Sender', value: 'fixed' },
    { label: 'Priority', value: 'priority' },
];

onMounted(async () => {
    const { data } = await api.get('/settings');
    settings.value = data;
});

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
</script>

<template>
    <div class="space-y-6 max-w-3xl">
        <div>
            <h1 class="text-2xl font-bold">الإعدادات</h1>
            <p class="text-sm text-slate-500 mt-1">تكوين الطابور، التوزيع، وإعادة المحاولة</p>
        </div>

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
