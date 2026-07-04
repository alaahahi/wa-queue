import { defineStore } from 'pinia';
import api from '@/api/client';

export const useSenderStore = defineStore('sender', {
    state: () => ({
        senders: [],
        loading: false,
    }),

    actions: {
        async fetchSenders() {
            this.loading = true;
            try {
                const { data } = await api.get('/senders');
                this.senders = data.data;
            } finally {
                this.loading = false;
            }
        },

        async toggle(id) {
            await api.post(`/senders/${id}/toggle`);
            await this.fetchSenders();
        },

        async checkStatus(id) {
            const { data } = await api.post(`/senders/${id}/check-status`);
            await this.fetchSenders();
            return data;
        },

        async redistribute(id) {
            const { data } = await api.post(`/senders/${id}/redistribute`);
            await this.fetchSenders();
            return data;
        },

        async create(payload) {
            await api.post('/senders', payload);
            await this.fetchSenders();
        },

        async update(id, payload) {
            await api.put(`/senders/${id}`, payload);
            await this.fetchSenders();
        },

        async delete(id) {
            await api.delete(`/senders/${id}`);
            await this.fetchSenders();
        },
    },
});
