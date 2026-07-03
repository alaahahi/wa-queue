<script setup>
import { ref, onMounted } from 'vue';
import { useQueueStore } from '@/stores/queue';
import { useSenderStore } from '@/stores/sender';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';

const queue = useQueueStore();
const senders = useSenderStore();
const toast = useToast();
const confirm = useConfirm();

const filters = ref({
    status: null,
    source: null,
    sender_id: null,
    phone: '',
});

const statusOptions = [
    { label: 'الكل', value: null },
    { label: 'Pending', value: 'pending' },
    { label: 'Assigned', value: 'assigned' },
    { label: 'Sending', value: 'sending' },
    { label: 'Sent', value: 'sent' },
    { label: 'Failed', value: 'failed' },
    { label: 'Cancelled', value: 'cancelled' },
];

const sourceOptions = [
    { label: 'الكل', value: null },
    { label: 'Contracts', value: 'contracts' },
    { label: 'CRM', value: 'crm' },
    { label: 'Sales', value: 'sales' },
    { label: 'Invoices', value: 'invoices' },
    { label: 'Support', value: 'support' },
    { label: 'Marketing', value: 'marketing' },
    { label: 'Appointments', value: 'appointments' },
];

const severityMap = {
    pending: 'warn',
    assigned: 'info',
    sending: 'info',
    sent: 'success',
    failed: 'danger',
    cancelled: 'secondary',
};

onMounted(async () => {
    await senders.fetchSenders();
    await queue.fetchQueue();
});

function applyFilters() {
    const params = Object.fromEntries(
        Object.entries(filters.value).filter(([, v]) => v !== null && v !== '')
    );
    queue.fetchQueue(params);
}

function confirmRetry(id) {
    confirm.require({
        message: 'إعادة محاولة إرسال هذه الرسالة؟',
        accept: async () => {
            await queue.retry(id);
            toast.add({ severity: 'success', summary: 'تمت إعادة الجدولة', life: 3000 });
        },
    });
}

function confirmCancel(id) {
    confirm.require({
        message: 'إلغاء هذه الرسالة؟',
        accept: async () => {
            await queue.cancel(id);
            toast.add({ severity: 'info', summary: 'تم الإلغاء', life: 3000 });
        },
    });
}
</script>

<template>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold">مراقبة الطابور</h1>
            <p class="text-sm text-slate-500 mt-1">جدول حي مع فلاتر وإجراءات إدارية</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
            <Select v-model="filters.status" :options="statusOptions" option-label="label" option-value="value" placeholder="الحالة" class="w-full" />
            <Select v-model="filters.source" :options="sourceOptions" option-label="label" option-value="value" placeholder="المصدر" class="w-full" />
            <Select
                v-model="filters.sender_id"
                :options="[{ label: 'الكل', value: null }, ...senders.senders.map(s => ({ label: s.name, value: s.id }))]"
                option-label="label"
                option-value="value"
                placeholder="المرسل"
                class="w-full"
            />
            <div class="flex gap-2">
                <InputText v-model="filters.phone" placeholder="الهاتف" class="w-full" />
                <Button icon="pi pi-search" @click="applyFilters" />
            </div>
        </div>

        <DataTable
            :value="queue.messages"
            :loading="queue.loading"
            paginator
            :rows="25"
            striped-rows
            size="small"
            class="text-sm"
        >
            <Column field="id" header="ID" style="width: 4rem" />
            <Column field="recipient_name" header="المستلم" />
            <Column field="phone" header="الهاتف">
                <template #body="{ data }">
                    <span dir="ltr">{{ data.phone }}</span>
                </template>
            </Column>
            <Column field="source" header="المصدر" />
            <Column field="event" header="الحدث" />
            <Column header="المرسل">
                <template #body="{ data }">
                    {{ data.sender?.name ?? '—' }}
                </template>
            </Column>
            <Column field="priority" header="الأولوية" style="width: 5rem" />
            <Column header="الحالة">
                <template #body="{ data }">
                    <Tag :value="data.status_label" :severity="severityMap[data.status]" />
                </template>
            </Column>
            <Column field="retry_count" header="محاولات" style="width: 5rem" />
            <Column header="إجراءات" style="width: 10rem">
                <template #body="{ data }">
                    <div class="flex gap-1">
                        <Button v-if="data.status === 'failed'" icon="pi pi-refresh" size="small" text @click="confirmRetry(data.id)" />
                        <Button v-if="!['sent', 'cancelled'].includes(data.status)" icon="pi pi-times" size="small" text severity="danger" @click="confirmCancel(data.id)" />
                    </div>
                </template>
            </Column>
        </DataTable>
    </div>
</template>
