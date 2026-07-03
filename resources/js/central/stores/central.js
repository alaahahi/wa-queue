import { defineStore } from 'pinia';
import api from '../api/client';

export const useCentralStore = defineStore('central', {
    state: () => ({
        overview: null,
        tenants: [],
        loading: false,
        checking: false,
        lastChecked: null,
    }),

    actions: {
        async fetchMonitor() {
            this.loading = true;
            try {
                const { data } = await api.get('/monitor');
                this.overview = data;
            } finally {
                this.loading = false;
            }
        },

        async checkAll() {
            this.checking = true;
            try {
                const { data } = await api.post('/monitor/check-all');
                this.overview = data;
                this.lastChecked = new Date();
            } finally {
                this.checking = false;
            }
        },

        async checkTenant(tenantId) {
            const { data } = await api.post(`/monitor/${tenantId}/check`);
            if (this.overview?.tenants) {
                const idx = this.overview.tenants.findIndex((t) => t.id === tenantId);
                if (idx !== -1) {
                    this.overview.tenants[idx] = data;
                }
            }
            this.lastChecked = new Date();
        },

        async fetchTenants() {
            const { data } = await api.get('/tenants');
            this.tenants = data.data;
        },

        async createTenant(payload) {
            const { data } = await api.post('/tenants', payload);
            await this.fetchTenants();
            await this.fetchMonitor();
            return data;
        },

        async deleteTenant(id) {
            await api.delete(`/tenants/${id}`);
            await this.fetchTenants();
            await this.fetchMonitor();
        },
    },
});
