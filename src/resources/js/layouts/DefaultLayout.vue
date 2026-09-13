<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();

async function logout() {
    await auth.logout();
    router.push({ name: 'login' });
}
</script>

<template>
    <div class="min-h-screen bg-slate-100">
        <header class="bg-white shadow">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                <RouterLink
                    :to="{ name: 'settings' }"
                    class="text-lg font-semibold text-slate-900 hover:text-indigo-600"
                >
                    Отзывы Яндекс.Карт
                </RouterLink>

                <div class="flex items-center gap-4">
                    <span class="text-sm text-slate-500">{{ auth.user?.email }}</span>
                    <button
                        type="button"
                        class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                        @click="logout"
                    >
                        Выйти
                    </button>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-6 py-8">
            <slot />
        </main>
    </div>
</template>