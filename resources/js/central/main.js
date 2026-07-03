import { createApp } from 'vue';
import { createPinia } from 'pinia';
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import CentralApp from './CentralApp.vue';
import router from './router';

const app = createApp(CentralApp);

app.use(createPinia());
app.use(router);
app.use(PrimeVue, {
    theme: { preset: Aura, options: { darkModeSelector: '.dark' } },
    ripple: true,
});
app.use(ToastService);
app.use(ConfirmationService);

app.mount('#central-app');
