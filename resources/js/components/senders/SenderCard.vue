<script setup>
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Button from 'primevue/button';

defineProps({
    sender: Object,
});

const emit = defineEmits(['check', 'toggle', 'redistribute']);

const statusMap = {
    online: { label: 'متصل', severity: 'success', dot: 'bg-emerald-500' },
    busy: { label: 'مشغول', severity: 'warn', dot: 'bg-amber-500' },
    offline: { label: 'غير متصل', severity: 'danger', dot: 'bg-red-500' },
};
</script>

<template>
    <Card class="!shadow-sm h-full">
        <template #title>
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="statusMap[sender.status]?.dot"></span>
                    <span class="truncate">{{ sender.name }}</span>
                </div>
                <Tag :value="statusMap[sender.status]?.label" :severity="statusMap[sender.status]?.severity" />
            </div>
        </template>
        <template #content>
            <div class="space-y-3 text-sm">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <div class="text-slate-500 text-xs">الهاتف</div>
                        <div dir="ltr" class="font-mono">{{ sender.phone }}</div>
                    </div>
                    <div>
                        <div class="text-slate-500 text-xs">API</div>
                        <div>{{ sender.api_connected ? 'Connected' : 'Offline' }}</div>
                    </div>
                    <div>
                        <div class="text-slate-500 text-xs">الطابور</div>
                        <div class="font-semibold">{{ sender.queue_count }}</div>
                    </div>
                    <div>
                        <div class="text-slate-500 text-xs">مرسل اليوم</div>
                        <div>{{ sender.today_sent }} / {{ sender.daily_limit }}</div>
                    </div>
                    <div>
                        <div class="text-slate-500 text-xs">التأخير</div>
                        <div>{{ sender.delay_seconds }} ث</div>
                    </div>
                    <div>
                        <div class="text-slate-500 text-xs">متوسط الاستجابة</div>
                        <div>{{ sender.avg_response_ms }} ms</div>
                    </div>
                </div>

                <div v-if="sender.last_sent_human" class="text-xs text-slate-500">
                    آخر رسالة: {{ sender.last_sent_human }}
                </div>
                <div v-if="sender.last_seen_human && sender.status === 'offline'" class="text-xs text-red-500">
                    آخر نشاط: {{ sender.last_seen_human }}
                </div>
                <div v-if="sender.last_error" class="text-xs text-red-500 truncate" :title="sender.last_error">
                    آخر خطأ: {{ sender.last_error }}
                </div>

                <div class="flex flex-wrap gap-2 pt-1">
                    <Button label="فحص الاتصال" size="small" outlined @click="emit('check', sender.id)" />
                    <Button
                        :label="sender.enabled ? 'تعطيل' : 'تفعيل'"
                        size="small"
                        severity="secondary"
                        outlined
                        @click="emit('toggle', sender.id)"
                    />
                    <Button
                        v-if="sender.status === 'offline'"
                        label="إعادة توزيع"
                        size="small"
                        severity="warn"
                        outlined
                        @click="emit('redistribute', sender.id)"
                    />
                </div>
            </div>
        </template>
    </Card>
</template>
