<script setup>
import { onMounted } from 'vue';
import { useCentralStore } from '../stores/central';
import { useToast } from 'primevue/usetoast';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Skeleton from 'primevue/skeleton';

const store = useCentralStore();
const toast = useToast();

onMounted(() => store.fetchMonitor());

async function checkAll() {
    await store.checkAll();
    toast.add({ severity: 'success', summary: 'تم فحص جميع الأرقام', life: 3000 });
}

async function checkTenant(id) {
    await store.checkTenant(id);
    toast.add({ severity: 'info', summary: 'تم فحص أرقام الزبون', life: 2500 });
}

const statusSeverity = { online: 'success', busy: 'warn', offline: 'danger' };
const statusLabel = { online: 'متصل', busy: 'مشغول', offline: 'غير متصل' };
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">مراقبة الزبائن</h1>
                <p class="text-sm text-slate-500 mt-1">كل الزبائن، دوميناتهم، وأرقام WhatsApp وحالة الربط</p>
            </div>
            <Button
                label="فحص الكل الآن"
                icon="pi pi-refresh"
                :loading="store.checking"
                @click="checkAll"
            />
        </div>

        <!-- Summary -->
        <div v-if="store.overview?.summary" class="grid grid-cols-2 md:grid-cols-5 gap-3">
            <Card v-for="(item, key) in {
                'زبائن': store.overview.summary.total_tenants,
                'نشط': store.overview.summary.active_tenants,
                'أرقام WA': store.overview.summary.total_senders,
                'متصل': store.overview.summary.online_senders,
                'غير متصل': store.overview.summary.offline_senders,
            }" :key="key" class="!shadow-sm">
                <template #content>
                    <div class="text-xs text-slate-500">{{ key }}</div>
                    <div class="text-2xl font-bold">{{ item }}</div>
                </template>
            </Card>
        </div>

        <div v-if="store.loading && !store.overview" class="space-y-4">
            <Skeleton v-for="i in 3" :key="i" height="200px" class="rounded-xl" />
        </div>

        <!-- Tenant cards -->
        <div v-else class="space-y-4">
            <Card
                v-for="tenant in store.overview?.tenants ?? []"
                :key="tenant.id"
                class="!shadow-sm"
            >
                <template #title>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-lg">{{ tenant.name || tenant.id }}</span>
                            <Tag :value="tenant.status" :severity="tenant.status === 'active' ? 'success' : 'warn'" />
                        </div>
                        <div class="flex gap-2">
                            <Button
                                label="فحص الربط"
                                icon="pi pi-whatsapp"
                                size="small"
                                outlined
                                :loading="store.checking"
                                @click="checkTenant(tenant.id)"
                            />
                            <a
                                v-if="tenant.dashboard_url"
                                :href="tenant.dashboard_url"
                                target="_blank"
                                class="inline-flex items-center gap-1 text-sm text-indigo-600 hover:underline px-3 py-1.5"
                            >
                                <i class="pi pi-external-link text-xs"></i>
                                فتح الداشبورد
                            </a>
                        </div>
                    </div>
                </template>
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 text-sm">
                        <div>
                            <span class="text-slate-500">الدومين:</span>
                            <div class="font-mono text-indigo-600">{{ tenant.primary_domain }}</div>
                        </div>
                        <div>
                            <span class="text-slate-500">هاتف الزبون:</span>
                            <div dir="ltr">{{ tenant.contact_phone || '—' }}</div>
                        </div>
                        <div>
                            <span class="text-slate-500">الطابور المعلق:</span>
                            <div class="font-semibold">{{ tenant.queue_stats?.pending ?? 0 }}</div>
                        </div>
                    </div>

                    <div v-if="!tenant.senders?.length" class="text-sm text-slate-500 py-4 text-center border border-dashed rounded-lg">
                        لا توجد أرقام WhatsApp — الزبون يضيفها من داشبورده
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                        <div
                            v-for="sender in tenant.senders"
                            :key="sender.id"
                            class="border rounded-lg p-4 dark:border-slate-700"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-medium">{{ sender.name }}</span>
                                <Tag
                                    :value="statusLabel[sender.status] || sender.status"
                                    :severity="statusSeverity[sender.status]"
                                />
                            </div>
                            <div class="space-y-1 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">الرقم:</span>
                                    <span dir="ltr" class="font-mono">{{ sender.phone }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">API:</span>
                                    <span :class="sender.api_connected ? 'text-emerald-600' : 'text-red-500'">
                                        {{ sender.api_connected ? 'Connected' : 'Offline' }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">مرسل اليوم:</span>
                                    <span>{{ sender.today_sent }} / {{ sender.daily_limit }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">الطابور:</span>
                                    <span>{{ sender.queue_count }}</span>
                                </div>
                                <div v-if="sender.last_error" class="text-xs text-red-500 truncate pt-1">
                                    {{ sender.last_error }}
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>
        </div>

        <p v-if="store.lastChecked" class="text-xs text-slate-400 text-center">
            آخر فحص: {{ store.lastChecked.toLocaleString('ar') }}
        </p>
    </div>
</template>
