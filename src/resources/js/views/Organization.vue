<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import {
    show as showOrganization,
    reviews as fetchReviews,
    sync as syncOrganization,
} from '../http/organizations';
import ReviewCard from '../components/ReviewCard.vue';
import Pagination from '../components/Pagination.vue';
import Spinner from '../components/Spinner.vue';
import DefaultLayout from '../layouts/DefaultLayout.vue';

const props = defineProps({
    id: { type: String, required: true },
});

const router = useRouter();

const organization = ref(null);
const reviews = ref([]);
const pagination = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(false);
const loadingReviews = ref(false);
const syncing = ref(false);
const error = ref(null);

async function loadOrganization() {
    try {
        const data = await showOrganization(props.id);
        organization.value = data.organization;
        reviews.value = data.reviews;
        pagination.value = data.pagination;
    } catch (e) {
        if (e.response?.status === 404) {
            error.value = 'Организация не найдена.';
        } else {
            error.value = 'Не удалось загрузить организацию.';
        }
    }
}

async function loadReviews(page = 1) {
    loadingReviews.value = true;
    try {
        const data = await fetchReviews(props.id, page);
        reviews.value = data.data;
        pagination.value = data.pagination;
    } catch (_) {
        error.value = 'Не удалось загрузить отзывы.';
    } finally {
        loadingReviews.value = false;
    }
}

async function reload() {
    loading.value = true;
    error.value = null;
    await loadOrganization();
    loading.value = false;
}

async function sync() {
    syncing.value = true;
    try {
        await syncOrganization(props.id);
        setTimeout(async () => {
            await reload();
            syncing.value = false;
        }, 30000);
    } catch (_) {
        syncing.value = false;
    }
}

function changePage(page) {
    loadReviews(page);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function backToSettings() {
    router.push({ name: 'settings' });
}

onMounted(() => {
    reload();
});

watch(
    () => props.id,
    () => {
        reload();
    }
);
</script>

<template>
    <DefaultLayout>
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <button
                    type="button"
                    class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                    @click="backToSettings"
                >
                    ← Назад
                </button>
                <h1 class="text-lg font-semibold text-slate-900">
                    {{ organization?.name || 'Организация' }}
                </h1>
            </div>
            <button
                type="button"
                :disabled="syncing"
                class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                @click="sync"
            >
                <span class="inline-flex items-center gap-2">
                    <Spinner v-if="syncing" size="xs" />
                    {{ syncing ? 'Синхронизация…' : 'Обновить данные' }}
                </span>
            </button>
        </div>

        <div class="space-y-6">
            <p
                v-if="error"
                class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700"
            >
                {{ error }}
            </p>

            <div
                v-if="loading"
                class="flex items-center justify-center py-20 text-slate-500"
            >
                <Spinner size="lg" />
            </div>

            <template v-else-if="organization">
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <h2 class="text-xl font-semibold text-slate-900">
                        {{ organization.name }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ organization.address }}
                    </p>

                    <div class="mt-6 flex flex-wrap items-center gap-6">
                        <div>
                            <div class="text-4xl font-bold text-slate-900">
                                {{
                                    organization.rating !== null
                                        ? Number(organization.rating).toFixed(1)
                                        : '—'
                                }}
                            </div>
                            <div class="text-xs uppercase tracking-wide text-slate-500">
                                рейтинг
                            </div>
                        </div>

                        <div>
                            <div class="text-2xl font-semibold text-slate-900">
                                {{ organization.ratings_count }}
                            </div>
                            <div class="text-xs uppercase tracking-wide text-slate-500">
                                оценок
                            </div>
                        </div>

                        <div>
                            <div class="text-2xl font-semibold text-slate-900">
                                {{ organization.reviews_count }}
                            </div>
                            <div class="text-xs uppercase tracking-wide text-slate-500">
                                отзывов
                            </div>
                        </div>

                        <div class="ml-auto text-right text-xs text-slate-500">
                            <div v-if="organization.last_parsed_at">
                                Обновлено:
                                {{ new Date(organization.last_parsed_at).toLocaleString('ru-RU') }}
                            </div>
                        </div>
                    </div>
                </section>

                <section>
                    <h2 class="mb-4 text-lg font-semibold text-slate-900">
                        Отзывы (показано {{ reviews.length }} из {{ pagination.total }})
                    </h2>

                    <div
                        v-if="loadingReviews"
                        class="flex items-center justify-center py-12 text-slate-500"
                    >
                        <Spinner size="lg" />
                    </div>

                    <div
                        v-else-if="reviews.length === 0"
                        class="rounded-2xl bg-white p-8 text-center text-sm text-slate-500 shadow-sm ring-1 ring-slate-100"
                    >
                        Отзывов пока нет.
                    </div>

                    <div v-else class="space-y-4">
                        <ReviewCard
                            v-for="review in reviews"
                            :key="review.id"
                            :review="review"
                        />
                    </div>

                    <div class="mt-8">
                        <Pagination
                            :current-page="pagination.current_page"
                            :last-page="pagination.last_page"
                            @change="changePage"
                        />
                    </div>
                </section>
            </template>
        </div>
    </DefaultLayout>
</template>