<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useOrganizationsStore } from '../stores/organizations';
import OrganizationCard from '../components/OrganizationCard.vue';
import Spinner from '../components/Spinner.vue';

const auth = useAuthStore();
const orgs = useOrganizationsStore();
const router = useRouter();

const yandexUrl = ref('');

async function logout() {
    await auth.logout();
    router.push({ name: 'login' });
}

async function addOrganization() {
    if (!yandexUrl.value.trim()) return;

    const created = await orgs.create(yandexUrl.value.trim());
    if (created) {
        yandexUrl.value = '';
    }
}

function onSync(id) {
    orgs.sync(id);
}

onMounted(async () => {
    await orgs.fetchAll();
    orgs.startPolling();
});

onBeforeUnmount(() => {
    orgs.stopPolling();
});
</script>

<template>
    <div class="min-h-screen bg-slate-100">
        <header class="bg-white shadow">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                <h1 class="text-lg font-semibold text-slate-900">Отзывы Яндекс.Карт</h1>
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

        <main class="mx-auto max-w-5xl space-y-6 px-6 py-8">
            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                <h2 class="text-lg font-semibold text-slate-900">Добавить организацию</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Вставьте ссылку на карточку организации в Яндекс.Картах
                </p>

                <form class="mt-4 flex gap-3" @submit.prevent="addOrganization">
                    <input
                        v-model="yandexUrl"
                        type="url"
                        placeholder="https://yandex.ru/maps/org/..."
                        class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                    />
                    <button
                        type="submit"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700"
                    >
                        Добавить
                    </button>
                </form>

                <p
                    v-if="orgs.error"
                    class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"
                >
                    {{ orgs.error }}
                </p>
            </section>

            <section>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">Мои организации</h2>
                    <span
                        v-if="orgs.loading"
                        class="flex items-center gap-2 text-sm text-slate-500"
                    >
                        <Spinner size="sm" /> Загрузка…
                    </span>
                </div>

                <div
                    v-if="!orgs.loading && orgs.items.length === 0"
                    class="rounded-2xl bg-white p-8 text-center text-sm text-slate-500 shadow-sm ring-1 ring-slate-100"
                >
                    Пока нет ни одной организации. Добавьте ссылку выше.
                </div>

                <div class="space-y-4">
                    <OrganizationCard
                        v-for="org in orgs.items"
                        :key="org.id"
                        :organization="org"
                        @sync="onSync"
                    />
                </div>
            </section>
        </main>
    </div>
</template>