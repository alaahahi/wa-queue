<script setup>
import { ref, onMounted } from 'vue';
import { useSenderStore } from '@/stores/sender';
import { useToast } from 'primevue/usetoast';
import SenderCard from '@/components/senders/SenderCard.vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Skeleton from 'primevue/skeleton';

const store = useSenderStore();
const toast = useToast();
const showDialog = ref(false);
const form = ref({
    name: '',
    phone: '',
    api_key: '',
    delay_seconds: 6,
    daily_limit: 500,
    priority: 5,
});

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
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">إدارة أرقام WhatsApp</h1>
                <p class="text-sm text-slate-500 mt-1">كل رقم له API Key مستقل — حالة الاتصال، الحد اليومي، وإعادة التوزيع</p>
            </div>
            <Button label="إضافة رقم" icon="pi pi-plus" @click="showDialog = true" />
        </div>

        <div v-if="store.loading" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Skeleton v-for="i in 2" :key="i" height="260px" class="rounded-xl" />
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <SenderCard
                v-for="sender in store.senders"
                :key="sender.id"
                :sender="sender"
                @check="onCheck"
                @toggle="onToggle"
                @redistribute="onRedistribute"
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
