<script setup>
import { ref, onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

const dark = ref(false);
const route = useRoute();

const links = [
    { to: '/', icon: 'pi pi-chart-line', label: 'مراقبة الزبائن' },
    { to: '/tenants', icon: 'pi pi-building', label: 'إدارة الزبائن' },
];

function toggleDark() {
    dark.value = !dark.value;
    document.documentElement.classList.toggle('dark', dark.value);
}
</script>

<template>
    <div class="min-h-screen flex bg-slate-50 dark:bg-slate-950">
        <aside class="w-64 shrink-0 border-l border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hidden md:flex flex-col">
            <div class="p-5 border-b border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="w-9 h-9 rounded-lg bg-indigo-600 text-white flex items-center justify-center">
                        <i class="pi pi-shield"></i>
                    </span>
                    <div>
                        <div class="font-bold text-sm">Central Admin</div>
                        <div class="text-xs text-slate-500">إدارة المنصة</div>
                    </div>
                </div>
            </div>
            <nav class="p-3 space-y-1 flex-1">
                <RouterLink
                    v-for="link in links"
                    :key="link.to"
                    :to="link.to"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors"
                    :class="route.path === link.to
                        ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300'
                        : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'"
                >
                    <i :class="link.icon"></i>
                    {{ link.label }}
                </RouterLink>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="h-14 border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 backdrop-blur flex items-center justify-between px-4 md:px-6">
                <span class="text-sm text-slate-500">لوحة إدارة مركزية — جميع الزبائن</span>
                <button
                    class="w-9 h-9 rounded-lg border border-slate-200 dark:border-slate-700 flex items-center justify-center"
                    @click="toggleDark"
                >
                    <i :class="dark ? 'pi pi-sun' : 'pi pi-moon'"></i>
                </button>
            </header>
            <main class="flex-1 p-4 md:p-6 overflow-auto">
                <router-view />
            </main>
        </div>
        <Toast position="top-left" />
        <ConfirmDialog />
    </div>
</template>
