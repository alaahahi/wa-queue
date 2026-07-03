<script setup>
defineProps({
    dark: Boolean,
});
defineEmits(['toggle-dark']);
</script>

<template>
    <header class="h-14 border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 backdrop-blur flex items-center justify-between px-4 md:px-6">
        <div class="text-sm text-slate-500">
            نظام إدارة طابور WhatsApp
        </div>
        <div class="flex items-center gap-3">
            <button
                class="w-9 h-9 rounded-lg border border-slate-200 dark:border-slate-700 flex items-center justify-center hover:bg-slate-100 dark:hover:bg-slate-800"
                @click="$emit('toggle-dark')"
            >
                <i :class="dark ? 'pi pi-sun' : 'pi pi-moon'"></i>
            </button>
            <div class="flex items-center gap-2 text-sm">
                <span class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900 flex items-center justify-center text-emerald-700">
                    <i class="pi pi-user text-xs"></i>
                </span>
                <span class="hidden sm:inline">Admin</span>
            </div>
        </div>
    </header>
</template>
