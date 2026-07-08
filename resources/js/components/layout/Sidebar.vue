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
                :aria-current="route.path === link.to ? 'page' : undefined"
                class="relative flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors duration-150"
                :class="route.path === link.to
                    ? 'bg-emerald-50 font-medium text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                    : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'"
            >
                <span
                    v-if="route.path === link.to"
                    class="absolute inset-y-1.5 right-0 w-1 rounded-full bg-emerald-600 dark:bg-emerald-400"
                    aria-hidden="true"
                ></span>
                <i :class="link.icon" class="w-5 text-center"></i>
                {{ link.label }}
            </RouterLink>
        </nav>
    </aside>
</template>
