<script setup>
import { ref, computed, onMounted } from 'vue';
import { useSenderStore } from '@/stores/sender';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import SenderCard from '@/components/senders/SenderCard.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Skeleton from 'primevue/skeleton';

const store = useSenderStore();
const toast = useToast();
const confirm = useConfirm();
const showDialog = ref(false);
const newApiKey = ref('');
const keyLoading = ref(false);
const form = ref({
    name: '',
    phone: '',
    api_key: '',
    delay_seconds: 6,
    daily_limit: 500,
    priority: 5,
});

const hasSender = computed(() => store.senders.length > 0);

onMounted(() => store.fetchSenders());

async function submit() {
    await store.create(form.value);
    showDialog.value = false;
    toast.add({ severity: 'success', summary: 'تمت إضافة الرقم', life: 3000 });
    form.value = { name: '', phone: '', api_key: '', delay_seconds: 6, daily_limit: 500, priority: 5 };
}

async function onCheck(id) {
    await store.checkStatus(id);
    toast.add({ severity: 'info', summary: 'تم فحص الاتصال', life: 3000 });
}

async function onToggle(id) {
    await store.toggle(id);
}

async function onRedistribute(id) {
    const result = await store.redistribute(id);
    toast.add({ severity: 'success', summary: result.message, life: 4000 });
}

function onDelete(id) {
    const sender = store.senders.find((s) => s.id === id);
    confirm.require({
        message: `حذف الرقم "${sender?.name}"؟ لا يمكن التراجع.`,
        header: 'تأكيد الحذف',
        acceptLabel: 'حذف',
        rejectLabel: 'إلغاء',
        acceptClass: 'p-button-danger',
        accept: async () => {
            await store.delete(id);
            toast.add({ severity: 'info', summary: 'تم حذف الرقم', life: 3000 });
        },
    });
}

async function updateApiKey() {
    const sender = store.senders[0];
    if (!sender || !newApiKey.value.trim()) {
        return;
    }

    keyLoading.value = true;
    try {
        await store.updateApiKey(sender.id, newApiKey.value.trim());
        newApiKey.value = '';
        toast.add({ severity: 'success', summary: 'تم تحديث API Key', life: 3000 });
    } finally {
        keyLoading.value = false;
    }
}

function actionLabel(action) {
    return action === 'added' ? 'إضافة' : 'تبديل';
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">رقم WhatsApp</h1>
                <p class="text-sm text-slate-500 mt-1">رقم واحد لكل حساب — API Key، حالة الاتصال، والحد اليومي</p>
            </div>
            <Button
                v-if="!hasSender && !store.loading"
                label="إضافة رقم"
                icon="pi pi-plus"
                @click="showDialog = true"
            />
        </div>

        <div v-if="store.loading" class="max-w-xl">
            <Skeleton height="260px" class="rounded-xl" />
        </div>

        <div v-else-if="!hasSender" class="max-w-xl rounded-xl border border-dashed border-slate-300 p-10 text-center">
            <i class="pi pi-whatsapp text-4xl text-emerald-500 mb-3" />
            <p class="text-slate-600 mb-4">لم يُضف رقم WhatsApp بعد</p>
            <Button label="إضافة رقم" icon="pi pi-plus" @click="showDialog = true" />
        </div>

        <div v-else class="max-w-xl space-y-4">
            <SenderCard
                :sender="store.senders[0]"
                @check="onCheck"
                @toggle="onToggle"
                @redistribute="onRedistribute"
                @delete="onDelete"
            />

            <Card>
                <template #title>TextMeBot API Key</template>
                <template #content>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <div>
                                <div class="font-mono" dir="ltr">{{ store.senders[0].api_key_hint }}</div>
                                <div class="text-xs text-slate-500 mt-1">
                                    آخر تحديث: {{ store.senders[0].api_key_rotated_human || '—' }}
                                </div>
                            </div>
                            <Tag
                                v-if="store.senders[0].api_key_rotation_due"
                                value="يحتاج تبديل"
                                severity="warn"
                            />
                            <Tag
                                v-else
                                value="ساري"
                                severity="success"
                            />
                        </div>

                        <div
                            v-if="store.senders[0].api_key_rotation_due"
                            class="text-xs text-amber-700 bg-amber-50 rounded-lg p-3"
                        >
                            يُفضّل تبديل المفتاح كل {{ store.senders[0].api_key_rotation_days }} أيام
                        </div>

                        <div>
                            <label class="text-sm text-slate-500">مفتاح جديد</label>
                            <InputText v-model="newApiKey" class="w-full font-mono" dir="ltr" placeholder="الصق المفتاح الجديد" />
                        </div>
                        <Button
                            label="حفظ المفتاح الجديد"
                            icon="pi pi-key"
                            class="w-full"
                            :loading="keyLoading"
                            :disabled="!newApiKey.trim()"
                            @click="updateApiKey"
                        />

                        <div v-if="store.senders[0].api_key_logs?.length" class="pt-2 border-t border-slate-100">
                            <div class="text-sm font-medium mb-2">سجل المفاتيح</div>
                            <DataTable :value="store.senders[0].api_key_logs" size="small" striped-rows>
                                <Column header="المفتاح">
                                    <template #body="{ data }">
                                        <span class="font-mono" dir="ltr">{{ data.key_hint }}</span>
                                    </template>
                                </Column>
                                <Column header="الإجراء">
                                    <template #body="{ data }">
                                        {{ actionLabel(data.action) }}
                                    </template>
                                </Column>
                                <Column field="created_human" header="التاريخ" />
                            </DataTable>
                        </div>
                    </div>
                </template>
            </Card>
        </div>

        <Dialog v-model:visible="showDialog" header="إضافة رقم WhatsApp" modal class="w-full max-w-lg">
            <div class="space-y-4">
                <div>
                    <label class="text-sm text-slate-500">الاسم</label>
                    <InputText v-model="form.name" class="w-full" />
                </div>
                <div>
                    <label class="text-sm text-slate-500">رقم الهاتف (+964...)</label>
                    <InputText v-model="form.phone" class="w-full" dir="ltr" />
                </div>
                <div>
                    <label class="text-sm text-slate-500">TextMeBot API Key</label>
                    <InputText v-model="form.api_key" class="w-full" dir="ltr" />
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="text-sm text-slate-500">التأخير (ث)</label>
                        <InputNumber v-model="form.delay_seconds" class="w-full" :min="1" />
                    </div>
                    <div>
                        <label class="text-sm text-slate-500">الحد اليومي</label>
                        <InputNumber v-model="form.daily_limit" class="w-full" :min="1" />
                    </div>
                    <div>
                        <label class="text-sm text-slate-500">الأولوية</label>
                        <InputNumber v-model="form.priority" class="w-full" :min="1" :max="10" />
                    </div>
                </div>
                <Button label="حفظ" class="w-full" @click="submit" />
            </div>
        </Dialog>
    </div>
</template>
