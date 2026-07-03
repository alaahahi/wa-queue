import { defineStore } from 'pinia';
import api from '@/api/client';
import { useToast } from 'primevue/usetoast';

export const useQueueStore = defineStore('queue', {
    state: () => ({
        messages: [],
        meta: {},
        filters: {},
        loading: false,
    }),

    actions: {
        async fetchQueue(filters = {}) {
            this.loading = true;
            this.filters = filters;
            try {
                const { data } = await api.get('/queue', { params: filters });
                this.messages = data.data;
                this.meta = data.meta;
            } finally {
                this.loading = false;
            }
        },

        async retry(id) {
            await api.post(`/queue/${id}/retry`);
            await this.fetchQueue(this.filters);
        },

        async cancel(id) {
            await api.post(`/queue/${id}/cancel`);
            await this.fetchQueue(this.filters);
        },

        async remove(id) {
            await api.delete(`/queue/${id}`);
            await this.fetchQueue(this.filters);
        },

        async move(id, senderId) {
            await api.post(`/queue/${id}/move`, { sender_id: senderId });
            await this.fetchQueue(this.filters);
        },
    },
});
