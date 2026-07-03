<script setup>
import { RouterLink, useRoute } from 'vue-router';

const route = useRoute();

const links = [
    { to: '/', icon: 'pi pi-home', label: 'لوحة القيادة' },
    { to: '/senders', icon: 'pi pi-whatsapp', label: 'الأرقام' },
    { to: '/queue', icon: 'pi pi-list', label: 'الطابور' },
    { to: '/analytics', icon: 'pi pi-chart-bar', label: 'التحليلات' },
    { to: '/settings', icon: 'pi pi-cog', label: 'الإعدادات' },
];
</script>

<template>
    <aside class="w-64 shrink-0 border-l border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hidden md:flex flex-col">
        <div class="p-5 border-b border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-2">
                <span class="w-9 h-9 rounded-lg bg-emerald-600 text-white flex items-center justify-center">
                    <i class="pi pi-whatsapp"></i>
                </span>
                <div>
                    <div class="font-bold text-sm">WA Queue</div>
                    <div class="text-xs text-slate-500">Enterprise</div>
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
                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                    : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'"
            >
                <i :class="link.icon"></i>
                {{ link.label }}
            </RouterLink>
        </nav>
    </aside>
</template>
