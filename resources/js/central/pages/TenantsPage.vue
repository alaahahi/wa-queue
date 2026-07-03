<script setup>
import { ref, computed, onMounted } from 'vue';
import { useCentralStore } from '../stores/central';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import Card from 'primevue/card';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';

const store = useCentralStore();
const toast = useToast();
const confirm = useConfirm();
const showDialog = ref(false);
const showAdvanced = ref(false);

const appHost = window.location.origin;

const form = ref({
    name: '',
    id: '',
    email: '',
    contact_phone: '',
    domain: '',
    status: 'active',
});

const previewUrl = computed(() => {
    const slug = form.value.id || 'acme';

    return `${appHost}/${slug}`;
});

const statusOptions = [
    { label: 'نشط', value: 'active' },
    { label: 'تجريبي', value: 'trial' },
    { label: 'موقوف', value: 'suspended' },
];

onMounted(() => store.fetchTenants());

function suggestSlug() {
    if (form.value.name && !form.value.id) {
        form.value.id = form.value.name
            .toLowerCase()
            .replace(/\s+/g, '-')
            .replace(/[^a-z0-9-]/g, '');
    }
}

async function submit() {
    try {
        await store.createTenant(form.value);
        showDialog.value = false;
        showAdvanced.value = false;
        toast.add({
            severity: 'success',
            summary: 'تم إنشاء الزبون',
            detail: previewUrl.value,
            life: 5000,
        });
        form.value = { name: '', id: '', email: '', contact_phone: '', domain: '', status: 'active' };
    } catch (e) {
        toast.add({ severity: 'error', summary: 'خطأ', detail: e.response?.data?.message || 'فشل الإنشاء', life: 5000 });
    }
}

function confirmDelete(tenant) {
    confirm.require({
        message: `حذف الزبون "${tenant.name}" وجميع بياناته؟`,
        acceptLabel: 'حذف',
        rejectLabel: 'إلغاء',
        acceptClass: 'p-button-danger',
        accept: async () => {
            await store.deleteTenant(tenant.id);
            toast.add({ severity: 'info', summary: 'تم الحذف', life: 3000 });
        },
    });
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">إدارة الزبائن</h1>
                <p class="text-sm text-slate-500 mt-1">أضف زبوناً بمعرّف فقط — الرابط: {{ appHost }}/المعرّف</p>
            </div>
            <Button label="زبون جديد" icon="pi pi-plus" @click="showDialog = true" />
        </div>

        <Card>
            <template #content>
                <DataTable :value="store.tenants" striped-rows size="small">
                    <Column field="name" header="الاسم" />
                    <Column field="id" header="المعرّف" />
                    <Column header="رابط الداشبورد">
                        <template #body="{ data }">
                            <a
                                v-if="data.dashboard_url"
                                :href="data.dashboard_url"
                                target="_blank"
                                class="text-indigo-600 font-mono text-sm hover:underline"
                                dir="ltr"
                            >
                                {{ data.dashboard_url }}
                            </a>
                        </template>
                    </Column>
                    <Column header="دومين مخصص">
                        <template #body="{ data }">
                            <a
                                v-if="data.custom_domain_url"
                                :href="data.custom_domain_url"
                                target="_blank"
                                class="text-slate-500 font-mono text-xs hover:underline"
                                dir="ltr"
                            >
                                {{ data.primary_domain }}
                            </a>
                            <span v-else class="text-slate-400">—</span>
                        </template>
                    </Column>
                    <Column field="contact_phone" header="هاتف الزبون">
                        <template #body="{ data }">
                            <span dir="ltr">{{ data.contact_phone || '—' }}</span>
                        </template>
                    </Column>
                    <Column field="email" header="البريد" />
                    <Column header="الحالة">
                        <template #body="{ data }">
                            <Tag :value="data.status" :severity="data.status === 'active' ? 'success' : 'warn'" />
                        </template>
                    </Column>
                    <Column header="إجراءات" style="width: 6rem">
                        <template #body="{ data }">
                            <Button icon="pi pi-trash" size="small" text severity="danger" @click="confirmDelete(data)" />
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>

        <Dialog v-model:visible="showDialog" header="إضافة زبون جديد" modal class="w-full max-w-lg">
            <div class="space-y-4">
                <div>
                    <label class="text-sm text-slate-500">اسم الشركة *</label>
                    <InputText v-model="form.name" class="w-full" @blur="suggestSlug" />
                </div>
                <div>
                    <label class="text-sm text-slate-500">المعرّف (slug) *</label>
                    <InputText v-model="form.id" class="w-full font-mono" dir="ltr" placeholder="kaml-kamal" />
                    <p class="text-xs text-indigo-600 mt-1 font-mono" dir="ltr">{{ previewUrl }}</p>
                </div>
                <div>
                    <label class="text-sm text-slate-500">هاتف الزبون</label>
                    <InputText v-model="form.contact_phone" class="w-full" dir="ltr" placeholder="+964..." />
                </div>
                <div>
                    <label class="text-sm text-slate-500">البريد</label>
                    <InputText v-model="form.email" class="w-full" dir="ltr" />
                </div>
                <div>
                    <label class="text-sm text-slate-500">الحالة</label>
                    <Select v-model="form.status" :options="statusOptions" option-label="label" option-value="value" class="w-full" />
                </div>

                <Button
                    :label="showAdvanced ? 'إخفاء الخيارات المتقدمة' : 'دومين مخصص (اختياري)'"
                    text
                    size="small"
                    @click="showAdvanced = !showAdvanced"
                />

                <div v-if="showAdvanced">
                    <label class="text-sm text-slate-500">دومين مخصص</label>
                    <InputText v-model="form.domain" class="w-full font-mono" dir="ltr" placeholder="kaml-kamal.intellij-app.com" />
                    <p class="text-xs text-slate-400 mt-1">اختياري — إذا أردت دومين/ساب دومين منفصل بدل المسار</p>
                </div>

                <Button label="إنشاء الزبون" icon="pi pi-check" class="w-full" @click="submit" />
            </div>
        </Dialog>
    </div>
</template>
