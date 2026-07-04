<script setup>
import { ref, computed, onMounted } from 'vue';
import { useSenderStore } from '@/stores/sender';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import SenderCard from '@/components/senders/SenderCard.vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Skeleton from 'primevue/skeleton';

const store = useSenderStore();
const toast = useToast();
const confirm = useConfirm();
const showDialog = ref(false);
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

        <div v-else class="max-w-xl">
            <SenderCard
                :sender="store.senders[0]"
                @check="onCheck"
                @toggle="onToggle"
                @redistribute="onRedistribute"
                @delete="onDelete"
            />
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
