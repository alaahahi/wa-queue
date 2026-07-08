<script setup>
import { computed } from 'vue';
import Tag from 'primevue/tag';
import Button from 'primevue/button';

const props = defineProps({
    sender: Object,
});

const emit = defineEmits(['check', 'toggle', 'redistribute', 'delete']);

const statusMap = {
    online: { label: 'متصل', severity: 'success', dot: 'bg-emerald-500', ring: 'bg-emerald-500/40' },
    busy: { label: 'مشغول', severity: 'warn', dot: 'bg-amber-500', ring: 'bg-amber-500/40' },
    offline: { label: 'غير متصل', severity: 'danger', dot: 'bg-red-500', ring: 'bg-red-500/40' },
};

const status = computed(() => statusMap[props.sender.status] ?? statusMap.offline);

const usagePct = computed(() => {
    const limit = props.sender.daily_limit || 0;
    if (!limit) return 0;
    return Math.min(100, Math.round((props.sender.today_sent / limit) * 100));
});

const usageColor = computed(() => {
    if (usagePct.value >= 90) return 'bg-red-500';
    if (usagePct.value >= 70) return 'bg-amber-500';
    return 'bg-emerald-500';
});

const stats = computed(() => [
    { icon: 'pi pi-inbox', label: 'الطابور', value: props.sender.queue_count },
    { icon: 'pi pi-clock', label: 'التأخير', value: `${props.sender.delay_seconds} ث` },
    { icon: 'pi pi-bolt', label: 'متوسط الاستجابة', value: `${props.sender.avg_response_ms ?? 0} ms` },
]);
</script>

<template>
    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden transition-shadow hover:shadow-md">
        <!-- Header -->
        <div class="flex items-center justify-between gap-3 p-4 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-3 min-w-0">
                <span class="relative flex w-3 h-3 shrink-0" aria-hidden="true">
                    <span
                        v-if="sender.status === 'online'"
                        class="absolute inline-flex h-full w-full rounded-full opacity-75 animate-ping"
                        :class="status.ring"
                    ></span>
                    <span class="relative inline-flex rounded-full w-3 h-3" :class="status.dot"></span>
                </span>
                <div class="min-w-0">
                    <div class="font-semibold truncate leading-tight">{{ sender.name }}</div>
                    <div dir="ltr" class="font-mono text-xs text-slate-500 truncate">{{ sender.phone }}</div>
                </div>
            </div>
            <Tag :value="status.label" :severity="status.severity" />
        </div>

        <div class="p-4 space-y-4">
            <!-- Daily usage -->
            <div>
                <div class="flex items-center justify-between text-xs mb-1.5">
                    <span class="text-slate-500">الاستخدام اليومي</span>
                    <span class="font-medium tabular-nums">
                        {{ sender.today_sent }} / {{ sender.daily_limit }}
                        <span class="text-slate-400">({{ usagePct }}%)</span>
                    </span>
                </div>
                <div class="h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :class="usageColor"
                        :style="{ width: usagePct + '%' }"
                    ></div>
                </div>
            </div>

            <!-- Stat tiles -->
            <div class="grid grid-cols-3 gap-2">
                <div
                    v-for="s in stats"
                    :key="s.label"
                    class="rounded-lg bg-slate-50 dark:bg-slate-800/50 p-2.5 text-center"
                >
                    <i :class="s.icon" class="text-slate-400 text-sm"></i>
                    <div class="text-sm font-semibold mt-1 tabular-nums leading-tight">{{ s.value }}</div>
                    <div class="text-[11px] text-slate-500 mt-0.5">{{ s.label }}</div>
                </div>
            </div>

            <!-- Meta -->
            <div class="space-y-1 text-xs">
                <div v-if="sender.last_sent_human" class="flex items-center gap-1.5 text-slate-500">
                    <i class="pi pi-send text-[10px]"></i> آخر رسالة: {{ sender.last_sent_human }}
                </div>
                <div v-if="sender.last_seen_human && sender.status === 'offline'" class="flex items-center gap-1.5 text-red-500">
                    <i class="pi pi-eye-slash text-[10px]"></i> آخر نشاط: {{ sender.last_seen_human }}
                </div>
                <div
                    v-if="sender.last_error"
                    class="flex items-start gap-1.5 text-red-500 rounded-lg bg-red-50 dark:bg-red-950/30 p-2"
                    :title="sender.last_error"
                >
                    <i class="pi pi-exclamation-circle text-[10px] mt-0.5"></i>
                    <span class="truncate">{{ sender.last_error }}</span>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-wrap gap-2 pt-1 border-t border-slate-100 dark:border-slate-800">
                <Button label="فحص الاتصال" icon="pi pi-sync" size="small" outlined class="mt-3" @click="emit('check', sender.id)" />
                <Button
                    :label="sender.enabled ? 'تعطيل' : 'تفعيل'"
                    :icon="sender.enabled ? 'pi pi-pause' : 'pi pi-play'"
                    size="small"
                    severity="secondary"
                    outlined
                    class="mt-3"
                    @click="emit('toggle', sender.id)"
                />
                <Button
                    v-if="sender.status === 'offline'"
                    label="إعادة توزيع"
                    icon="pi pi-share-alt"
                    size="small"
                    severity="warn"
                    outlined
                    class="mt-3"
                    @click="emit('redistribute', sender.id)"
                />
                <Button
                    label="حذف"
                    icon="pi pi-trash"
                    size="small"
                    severity="danger"
                    outlined
                    class="mt-3 ms-auto"
                    @click="emit('delete', sender.id)"
                />
            </div>
        </div>
    </div>
</template>
