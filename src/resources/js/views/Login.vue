<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const email = ref('demo@example.com');
const password = ref('password');

async function submit() {
    const ok = await auth.login(email.value, password.value);
    if (ok) {
        const redirect = route.query.redirect || '/settings';
        router.push(redirect);
    }
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-slate-100 px-4">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold text-slate-900">Вход</h1>
                <p class="mt-2 text-sm text-slate-500">
                    Войдите, чтобы работать с отзывами Яндекс.Карт
                </p>
            </div>

            <form
                @submit.prevent="submit"
                class="space-y-4 rounded-2xl bg-white p-8 shadow-lg"
            >
                <div>
                    <label class="block text-sm font-medium text-slate-700">
                        Email
                    </label>
                    <input
                        v-model="email"
                        type="email"
                        required
                        autocomplete="email"
                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">
                        Пароль
                    </label>
                    <input
                        v-model="password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                    />
                </div>

                <p
                    v-if="auth.error"
                    class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"
                >
                    {{ auth.error }}
                </p>

                <button
                    type="submit"
                    :disabled="auth.loading"
                    class="w-full rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ auth.loading ? 'Вход…' : 'Войти' }}
                </button>
            </form>
        </div>
    </div>
</template>