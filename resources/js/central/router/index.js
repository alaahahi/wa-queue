import { createRouter, createWebHistory } from 'vue-router';
import MonitorPage from '../pages/MonitorPage.vue';
import TenantsPage from '../pages/TenantsPage.vue';
import ToolsPage from '../pages/ToolsPage.vue';

const routes = [
    { path: '/', name: 'monitor', component: MonitorPage },
    { path: '/tenants', name: 'tenants', component: TenantsPage },
    { path: '/tools', name: 'tools', component: ToolsPage },
];

export default createRouter({
    history: createWebHistory('/admin'),
    routes,
});
