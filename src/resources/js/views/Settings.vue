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
                <h1 class="text-lg font-semibold text-slate-900">
                    Отзывы Яндекс.Карт
                </h1>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-slate-500">
                        {{ auth.user?.email }}
                    </span>
                    <button
                        @click="logout"
                        class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                    >
                        Выйти
                    </button>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-6 py-8">
            <div class="rounded-2xl bg-white p-8 shadow">
                <h2 class="text-xl font-semibold text-slate-900">Настройки</h2>
                <p class="mt-2 text-sm text-slate-500">
                    Здесь будет форма для вставки ссылки на организацию в Яндекс.Картах.
                </p>
            </div>
        </main>
    </div>
</template>