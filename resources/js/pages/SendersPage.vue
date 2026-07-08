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
import Password from 'primevue/password';
import Skeleton from 'primevue/skeleton';

const store = useSenderStore();
const toast = useToast();
const confirm = useConfirm();

const showAddDialog = ref(false);
const showKeyDialog = ref(false);
const keyLoading = ref(false);

const form = ref({
    name: '',
    phone: '',
    api_key: '',
    delay_seconds: 6,
    daily_limit: 500,
    priority: 5,
});
const formErrors = ref({});

const newApiKey = ref('');
const keyError = ref('');

const hasSender = computed(() => store.senders.length > 0);
const sender = computed(() => store.senders[0] ?? null);

onMounted(() => store.fetchSenders());

function validateForm() {
    const errors = {};
    if (!form.value.name.trim()) errors.name = 'الاسم مطلوب';
    if (!form.value.phone.trim()) errors.phone = 'رقم الهاتف مطلوب';
    if (!form.value.api_key.trim()) errors.api_key = 'مفتاح API مطلوب';
    else if (form.value.api_key.trim().length < 4) errors.api_key = 'المفتاح قصير جداً';
    formErrors.value = errors;
    return Object.keys(errors).length === 0;
}

async function submit() {
    if (!validateForm()) return;

    keyLoading.value = true;
    try {
        await store.create(form.value);
        showAddDialog.value = false;
        toast.add({ severity: 'success', summary: 'تمت إضافة الرقم', detail: 'تم حفظ مفتاح API بنجاح', life: 3000 });
        form.value = { name: '', phone: '', api_key: '', delay_seconds: 6, daily_limit: 500, priority: 5 };
        formErrors.value = {};
    } catch (e) {
        toast.add({ severity: 'error', summary: 'فشل الحفظ', detail: e.response?.data?.message || 'حدث خطأ', life: 5000 });
    } finally {
        keyLoading.value = false;
    }
}

function openKeyDialog() {
    newApiKey.value = '';
    keyError.value = '';
    showKeyDialog.value = true;
}

function validateKey() {
    const v = newApiKey.value.trim();
    if (!v) {
        keyError.value = 'المفتاح مطلوب';
        return false;
    }
    if (v.length < 4) {
        keyError.value = 'المفتاح قصير جداً';
        return false;
    }
    keyError.value = '';
    return true;
}

async function saveApiKey() {
    if (!sender.value || !validateKey()) return;

    keyLoading.value = true;
    try {
        await store.updateApiKey(sender.value.id, newApiKey.value.trim());
        showKeyDialog.value = false;
        newApiKey.value = '';
        toast.add({ severity: 'success', summary: 'تم تبديل المفتاح', detail: 'المفتاح الجديد ساري الآن', life: 3000 });
    } catch (e) {
        keyError.value = e.response?.data?.message || 'فشل تبديل المفتاح — حاول مجدداً';
    } finally {
        keyLoading.value = false;
    }
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
    const s = store.senders.find((x) => x.id === id);
    confirm.require({
        message: `حذف الرقم "${s?.name}"؟ لا يمكن التراجع.`,
        header: 'تأكيد الحذف',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'حذف',
        rejectLabel: 'إلغاء',
        acceptClass: 'p-button-danger',
        accept: async () => {
            await store.delete(id);
            toast.add({ severity: 'info', summary: 'تم حذف الرقم', life: 3000 });
        },
    });
}

function actionMeta(action) {
    return action === 'added'
        ? { label: 'إضافة', severity: 'success', icon: 'pi pi-plus' }
        : { label: 'تبديل', severity: 'warn', icon: 'pi pi-refresh' };
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">رقم WhatsApp</h1>
                <p class="text-sm text-slate-500 mt-1">رقم واحد لكل حساب — مفتاح API، حالة الاتصال، والحد اليومي</p>
            </div>
            <Button
                v-if="!hasSender && !store.loading"
                label="إضافة رقم"
                icon="pi pi-plus"
                @click="showAddDialog = true"
            />
        </div>

        <div v-if="store.loading" class="max-w-xl space-y-4">
            <Skeleton height="240px" class="rounded-xl" />
            <Skeleton height="180px" class="rounded-xl" />
        </div>

        <div
            v-else-if="!hasSender"
            class="max-w-xl rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-10 text-center"
        >
            <i class="pi pi-whatsapp text-4xl text-emerald-500 mb-3" />
            <p class="text-slate-600 dark:text-slate-300 mb-4">لم يُضف رقم WhatsApp بعد</p>
            <Button label="إضافة رقم" icon="pi pi-plus" @click="showAddDialog = true" />
        </div>

        <div v-else class="max-w-xl space-y-4">
            <SenderCard
                :sender="sender"
                @check="onCheck"
                @toggle="onToggle"
                @redistribute="onRedistribute"
                @delete="onDelete"
            />

            <Card>
                <template #title>
                    <div class="flex items-center justify-between gap-3">
                        <span class="flex items-center gap-2">
                            <i class="pi pi-key text-indigo-500" />
                            مفتاح TextMeBot
                        </span>
                        <Tag
                            :value="sender.api_key_rotation_due ? 'يحتاج تبديل' : 'ساري'"
                            :severity="sender.api_key_rotation_due ? 'warn' : 'success'"
                            :icon="sender.api_key_rotation_due ? 'pi pi-exclamation-triangle' : 'pi pi-check-circle'"
                        />
                    </div>
                </template>
                <template #content>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 dark:bg-slate-800/50 p-3">
                            <div>
                                <div class="font-mono text-base tracking-wider" dir="ltr">{{ sender.api_key_hint }}</div>
                                <div class="text-xs text-slate-500 mt-1">
                                    <i class="pi pi-clock text-[10px]" />
                                    آخر تحديث: {{ sender.api_key_rotated_human || '—' }}
                                </div>
                            </div>
                            <Button
                                label="تبديل المفتاح"
                                icon="pi pi-refresh"
                                size="small"
                                outlined
                                aria-label="تبديل مفتاح API"
                                @click="openKeyDialog"
                            />
                        </div>

                        <div
                            v-if="sender.api_key_rotation_due"
                            role="alert"
                            class="flex items-start gap-2 text-xs text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/30 rounded-lg p-3"
                        >
                            <i class="pi pi-exclamation-triangle mt-0.5" />
                            <span>مضى أكثر من {{ sender.api_key_rotation_days }} أيام على آخر تبديل — يُنصح بتحديث المفتاح للحفاظ على الأمان.</span>
                        </div>

                        <div v-if="sender.api_key_logs?.length" class="pt-2 border-t border-slate-100 dark:border-slate-800">
                            <div class="text-sm font-medium mb-2">سجل المفاتيح</div>
                            <DataTable :value="sender.api_key_logs" size="small" striped-rows>
                                <Column header="المفتاح">
                                    <template #body="{ data }">
                                        <span class="font-mono" dir="ltr">{{ data.key_hint }}</span>
                                    </template>
                                </Column>
                                <Column header="الإجراء">
                                    <template #body="{ data }">
                                        <Tag
                                            :value="actionMeta(data.action).label"
                                            :severity="actionMeta(data.action).severity"
                                            :icon="actionMeta(data.action).icon"
                                        />
                                    </template>
                                </Column>
                                <Column field="created_human" header="التاريخ" />
                            </DataTable>
                        </div>
                        <p v-else class="text-xs text-slate-400 text-center py-2">لا يوجد سجل بعد</p>
                    </div>
                </template>
            </Card>
        </div>

        <!-- تبديل المفتاح -->
        <Dialog v-model:visible="showKeyDialog" header="تبديل مفتاح API" modal class="w-full max-w-md">
            <div class="space-y-4">
                <div
                    class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/50 rounded-lg p-3"
                >
                    <i class="pi pi-info-circle mt-0.5 text-indigo-500" />
                    <span>سيتوقف المفتاح الحالي فوراً بعد الحفظ، وسيبدأ استخدام المفتاح الجديد.</span>
                </div>

                <div>
                    <label for="new-api-key" class="text-sm text-slate-600 dark:text-slate-300">
                        المفتاح الجديد <span class="text-red-500">*</span>
                    </label>
                    <Password
                        input-id="new-api-key"
                        v-model="newApiKey"
                        toggle-mask
                        :feedback="false"
                        fluid
                        input-class="font-mono"
                        placeholder="الصق مفتاح TextMeBot الجديد"
                        :invalid="!!keyError"
                        @blur="validateKey"
                        @keyup.enter="saveApiKey"
                    />
                    <small v-if="keyError" role="alert" class="text-red-500 mt-1 block">{{ keyError }}</small>
                </div>
            </div>
            <template #footer>
                <Button label="إلغاء" text severity="secondary" :disabled="keyLoading" @click="showKeyDialog = false" />
                <Button label="حفظ المفتاح" icon="pi pi-check" :loading="keyLoading" @click="saveApiKey" />
            </template>
        </Dialog>

        <!-- إضافة رقم -->
        <Dialog v-model:visible="showAddDialog" header="إضافة رقم WhatsApp" modal class="w-full max-w-lg">
            <div class="space-y-4">
                <div>
                    <label for="s-name" class="text-sm text-slate-600 dark:text-slate-300">
                        الاسم <span class="text-red-500">*</span>
                    </label>
                    <InputText id="s-name" v-model="form.name" class="w-full" :invalid="!!formErrors.name" />
                    <small v-if="formErrors.name" role="alert" class="text-red-500 mt-1 block">{{ formErrors.name }}</small>
                </div>
                <div>
                    <label for="s-phone" class="text-sm text-slate-600 dark:text-slate-300">
                        رقم الهاتف (+964...) <span class="text-red-500">*</span>
                    </label>
                    <InputText id="s-phone" v-model="form.phone" class="w-full" dir="ltr" :invalid="!!formErrors.phone" />
                    <small v-if="formErrors.phone" role="alert" class="text-red-500 mt-1 block">{{ formErrors.phone }}</small>
                </div>
                <div>
                    <label for="s-key" class="text-sm text-slate-600 dark:text-slate-300">
                        مفتاح TextMeBot API <span class="text-red-500">*</span>
                    </label>
                    <Password
                        input-id="s-key"
                        v-model="form.api_key"
                        toggle-mask
                        :feedback="false"
                        fluid
                        input-class="font-mono"
                        :invalid="!!formErrors.api_key"
                    />
                    <small v-if="formErrors.api_key" role="alert" class="text-red-500 mt-1 block">{{ formErrors.api_key }}</small>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="text-sm text-slate-600 dark:text-slate-300">التأخير (ث)</label>
                        <InputNumber v-model="form.delay_seconds" class="w-full" :min="1" />
                    </div>
                    <div>
                        <label class="text-sm text-slate-600 dark:text-slate-300">الحد اليومي</label>
                        <InputNumber v-model="form.daily_limit" class="w-full" :min="1" />
                    </div>
                    <div>
                        <label class="text-sm text-slate-600 dark:text-slate-300">الأولوية</label>
                        <InputNumber v-model="form.priority" class="w-full" :min="1" :max="10" />
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="إلغاء" text severity="secondary" :disabled="keyLoading" @click="showAddDialog = false" />
                <Button label="حفظ الرقم" icon="pi pi-check" :loading="keyLoading" @click="submit" />
            </template>
        </Dialog>
    </div>
</template>
