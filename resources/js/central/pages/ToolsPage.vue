<script setup>
import { ref, onMounted } from 'vue';
import { useCentralStore } from '../stores/central';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import api from '../api/client';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';

const store = useCentralStore();
const toast = useToast();
const confirm = useConfirm();

const status = ref(null);
const logs = ref('');
const logMeta = ref(null);
const output = ref('');
const loadingLogs = ref(false);
const running = ref('');
const selectedTenant = ref(null);

const tenantOptions = ref([]);

onMounted(async () => {
    await Promise.all([loadStatus(), loadLogs(), store.fetchTenants()]);
    tenantOptions.value = [
        { label: 'كل الزبائن', value: null },
        ...store.tenants.map((t) => ({ label: `${t.name} (${t.id})`, value: t.id })),
    ];
});

async function loadStatus() {
    const { data } = await api.get('/system/status');
    status.value = data;
}

async function loadLogs() {
    loadingLogs.value = true;
    try {
        const { data } = await api.get('/system/logs', { params: { lines: 400 } });
        logs.value = data.content || 'لا يوجد محتوى';
        logMeta.value = data;
    } finally {
        loadingLogs.value = false;
    }
}

function confirmClearLogs() {
    confirm.require({
        message: 'مسح كامل محتوى سجل الأخطاء؟ لا يمكن التراجع.',
        header: 'تأكيد المسح',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'مسح',
        rejectLabel: 'إلغاء',
        acceptClass: 'p-button-danger',
        accept: clearLogs,
    });
}

async function clearLogs() {
    running.value = 'clear-logs';
    try {
        const { data } = await api.post('/system/clear-logs');
        toast.add({
            severity: data.success ? 'success' : 'error',
            summary: data.message,
            life: 3000,
        });
        await Promise.all([loadLogs(), loadStatus()]);
    } catch (e) {
        toast.add({ severity: 'error', summary: 'فشل المسح', detail: e.response?.data?.message || e.message, life: 5000 });
    } finally {
        running.value = '';
    }
}

function runMigration(type) {
    const isCentral = type === 'central';
    const message = isCentral
        ? 'تشغيل migrate للقاعدة المركزية؟'
        : `تشغيل tenants:migrate${selectedTenant.value ? ` للزبون ${selectedTenant.value}` : ' لكل الزبائن'}؟`;

    confirm.require({
        message,
        header: 'تأكيد Migration',
        acceptLabel: 'تنفيذ',
        rejectLabel: 'إلغاء',
        accept: () => (isCentral ? migrateCentral() : migrateTenants()),
    });
}

async function migrateCentral() {
    running.value = 'central';
    output.value = '';
    try {
        const { data } = await api.post('/system/migrate-central');
        output.value = data.output;
        toast.add({
            severity: data.success ? 'success' : 'error',
            summary: data.success ? 'تمت الهجرة المركزية' : 'فشلت الهجرة',
            life: 4000,
        });
        await loadLogs();
    } catch (e) {
        output.value = e.response?.data?.output || e.message;
        toast.add({ severity: 'error', summary: 'خطأ', detail: output.value, life: 6000 });
    } finally {
        running.value = '';
    }
}

async function migrateTenants() {
    running.value = 'tenants';
    output.value = '';
    try {
        const { data } = await api.post('/system/migrate-tenants', {
            tenant_id: selectedTenant.value,
        });
        output.value = data.output;
        toast.add({
            severity: data.success ? 'success' : 'error',
            summary: data.success ? 'تمت هجرة الزبائن' : 'فشلت الهجرة',
            life: 4000,
        });
        await loadLogs();
    } catch (e) {
        output.value = e.response?.data?.output || e.message;
        toast.add({ severity: 'error', summary: 'خطأ', detail: output.value, life: 6000 });
    } finally {
        running.value = '';
    }
}

function formatBytes(bytes) {
    if (!bytes) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let i = 0;
    while (size >= 1024 && i < units.length - 1) {
        size /= 1024;
        i++;
    }
    return `${size.toFixed(1)} ${units[i]}`;
}
</script>

<template>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold">أدوات النظام</h1>
            <p class="text-sm text-slate-500 mt-1">تنفيذ Migrations وعرض سجل الأخطاء</p>
        </div>

        <div v-if="status" class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <Card>
                <template #content>
                    <div class="text-xs text-slate-500">البيئة</div>
                    <div class="font-semibold">{{ status.app_env }}</div>
                </template>
            </Card>
            <Card>
                <template #content>
                    <div class="text-xs text-slate-500">الزبائن</div>
                    <div class="font-semibold">{{ status.tenants_count }}</div>
                </template>
            </Card>
            <Card>
                <template #content>
                    <div class="text-xs text-slate-500">PHP</div>
                    <div class="font-semibold font-mono text-sm" dir="ltr">{{ status.php_version }}</div>
                </template>
            </Card>
            <Card>
                <template #content>
                    <div class="text-xs text-slate-500">حجم اللوغ</div>
                    <div class="font-semibold">{{ formatBytes(status.log_size) }}</div>
                </template>
            </Card>
        </div>

        <Card>
            <template #title>Migrations</template>
            <template #content>
                <div class="space-y-4">
                    <div class="flex flex-wrap gap-3">
                        <Button
                            label="Migrate مركزي"
                            icon="pi pi-database"
                            :loading="running === 'central'"
                            @click="runMigration('central')"
                        />
                        <Select
                            v-model="selectedTenant"
                            :options="tenantOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="كل الزبائن"
                            class="min-w-56"
                        />
                        <Button
                            label="Migrate زبائن"
                            icon="pi pi-users"
                            severity="secondary"
                            :loading="running === 'tenants'"
                            @click="runMigration('tenants')"
                        />
                    </div>

                    <div v-if="output" class="rounded-lg bg-slate-950 text-emerald-300 p-4 text-xs font-mono whitespace-pre-wrap max-h-64 overflow-auto" dir="ltr">
                        {{ output }}
                    </div>
                </div>
            </template>
        </Card>

        <Card>
            <template #title>
                <div class="flex items-center justify-between gap-3">
                    <span>سجل الأخطاء (laravel.log)</span>
                    <div class="flex items-center gap-1">
                        <Button
                            icon="pi pi-refresh"
                            text
                            rounded
                            aria-label="تحديث السجل"
                            :loading="loadingLogs"
                            @click="loadLogs"
                        />
                        <Button
                            icon="pi pi-trash"
                            text
                            rounded
                            severity="danger"
                            aria-label="مسح السجل"
                            :loading="running === 'clear-logs'"
                            @click="confirmClearLogs"
                        />
                    </div>
                </div>
            </template>
            <template #content>
                <div class="flex items-center gap-2 mb-3 text-xs text-slate-500">
                    <Tag v-if="logMeta?.exists" value="موجود" severity="success" />
                    <Tag v-else value="غير موجود" severity="warn" />
                    <span v-if="logMeta?.updated_at">آخر تحديث: {{ new Date(logMeta.updated_at).toLocaleString('ar') }}</span>
                </div>
                <Textarea
                    :model-value="logs"
                    readonly
                    rows="18"
                    class="w-full font-mono text-xs"
                    dir="ltr"
                />
            </template>
        </Card>
    </div>
</template>
