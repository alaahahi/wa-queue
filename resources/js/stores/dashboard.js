import { defineStore } from 'pinia';
import api from '@/api/client';

export const useDashboardStore = defineStore('dashboard', {
    state: () => ({
        stats: null,
        senders: [],
        settings: null,
        analytics: null,
        loading: false,
        lastUpdated: null,
    }),

    actions: {
        async fetchDashboard() {
            this.loading = true;
            try {
                const { data } = await api.get('/dashboard');
                this.stats = data.stats;
                this.senders = data.senders;
                this.settings = data.settings;
                this.lastUpdated = new Date();
            } finally {
                this.loading = false;
            }
        },

        async fetchAnalytics() {
            const { data } = await api.get('/analytics');
            this.analytics = data;
        },

        startPolling(intervalMs = 10000) {
            this.fetchDashboard();
            this._pollId = setInterval(() => this.fetchDashboard(), intervalMs);
        },

        stopPolling() {
            if (this._pollId) clearInterval(this._pollId);
        },
    },
});
