<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useOrganizationsStore } from '../stores/organizations';
import OrganizationCard from '../components/OrganizationCard.vue';
import Spinner from '../components/Spinner.vue';
import DefaultLayout from '../layouts/DefaultLayout.vue';

const orgs = useOrganizationsStore();
const yandexUrl = ref('');

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

function onDelete(id) {
    if (!window.confirm('Удалить эту организацию вместе со всеми отзывами?')) return;
    orgs.remove(id);
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
    <DefaultLayout>
        <div class="space-y-6">
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
                        @delete="onDelete"
                    />
                </div>
            </section>
        </div>
    </DefaultLayout>
</template>