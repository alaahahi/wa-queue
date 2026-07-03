import { createRouter, createWebHistory } from 'vue-router';
import { tenantBasePath } from '@/utils/tenant';
import DashboardPage from '@/pages/DashboardPage.vue';
import SendersPage from '@/pages/SendersPage.vue';
import QueuePage from '@/pages/QueuePage.vue';
import AnalyticsPage from '@/pages/AnalyticsPage.vue';
import SettingsPage from '@/pages/SettingsPage.vue';

const routes = [
    { path: '/', name: 'dashboard', component: DashboardPage },
    { path: '/senders', name: 'senders', component: SendersPage },
    { path: '/queue', name: 'queue', component: QueuePage },
    { path: '/analytics', name: 'analytics', component: AnalyticsPage },
    { path: '/settings', name: 'settings', component: SettingsPage },
];

export default createRouter({
    history: createWebHistory(`${tenantBasePath()}/`),
    routes,
});
