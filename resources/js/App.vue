<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import Sidebar from '@/components/layout/Sidebar.vue';
import Topbar from '@/components/layout/Topbar.vue';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

const dark = ref(document.documentElement.classList.contains('dark'));
const route = useRoute();

function toggleDark() {
    dark.value = !dark.value;
    document.documentElement.classList.toggle('dark', dark.value);
    localStorage.setItem('wa-dark', dark.value ? '1' : '0');
}

onMounted(() => {
    if (localStorage.getItem('wa-dark') === '1') {
        dark.value = true;
        document.documentElement.classList.add('dark');
    }
});
</script>

<template>
    <div class="min-h-screen flex">
        <Sidebar />
        <div class="flex-1 flex flex-col min-w-0">
            <Topbar :dark="dark" @toggle-dark="toggleDark" />
            <main class="flex-1 p-4 md:p-6 overflow-auto">
                <router-view :key="route.path" />
            </main>
        </div>
        <Toast position="top-left" />
        <ConfirmDialog />
    </div>
</template>
