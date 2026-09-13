<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import Spinner from './Spinner.vue';

const props = defineProps({
    organization: { type: Object, required: true },
});

const emit = defineEmits(['sync', 'delete']);

const statusBadge = computed(() => {
    const s = props.organization.parse_status;
    switch (s) {
        case 'pending':
            return { text: 'В очереди', classes: 'bg-slate-100 text-slate-700' };
        case 'running':
            return { text: 'Парсинг…', classes: 'bg-indigo-100 text-indigo-700' };
        case 'ok':
            return { text: 'Готово', classes: 'bg-green-100 text-green-700' };
        case 'failed':
            return { text: 'Ошибка', classes: 'bg-red-100 text-red-700' };
        case 'layout_changed':
            return { text: 'Разметка изменилась', classes: 'bg-amber-100 text-amber-800' };
        default:
            return { text: s, classes: 'bg-slate-100 text-slate-700' };
    }
});

const isProcessing = computed(
    () =>
        props.organization.parse_status === 'pending' ||
        props.organization.parse_status === 'running'
);
</script>

<template>
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <h3 class="truncate text-lg font-semibold text-slate-900">
                        {{ organization.name || 'Без названия' }}
                    </h3>
                    <span
                        :class="[
                            'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium',
                            statusBadge.classes,
                        ]"
                    >
                        <Spinner v-if="isProcessing" size="xs" />
                        {{ statusBadge.text }}
                    </span>
                </div>

                <p class="mt-1 truncate text-sm text-slate-500">
                    {{ organization.address || organization.yandex_url }}
                </p>

                <div class="mt-3 flex flex-wrap items-center gap-4 text-sm text-slate-700">
                    <div v-if="organization.rating !== null">
                        <span class="text-2xl font-bold text-slate-900">
                            {{ Number(organization.rating).toFixed(1) }}
                        </span>
                        <span class="ml-1 text-xs text-slate-500">рейтинг</span>
                    </div>

                    <div v-if="organization.ratings_count">
                        <span class="font-semibold">{{ organization.ratings_count }}</span>
                        <span class="ml-1 text-xs text-slate-500">оценок</span>
                    </div>

                    <div v-if="organization.reviews_count">
                        <span class="font-semibold">{{ organization.reviews_count }}</span>
                        <span class="ml-1 text-xs text-slate-500">отзывов</span>
                    </div>
                </div>

                <p
                    v-if="organization.parse_error"
                    class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700"
                >
                    {{ organization.parse_error }}
                </p>
            </div>

            <div class="flex flex-col gap-2">
                <RouterLink
                    :to="{ name: 'organization', params: { id: organization.id } }"
                    class="rounded-lg bg-indigo-600 px-3 py-1.5 text-center text-sm font-medium text-white transition hover:bg-indigo-700"
                >
                    Открыть
                </RouterLink>
                <button
                    type="button"
                    :disabled="isProcessing"
                    class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    @click="emit('sync', organization.id)"
                >
                    Синхронизировать
                </button>
                <button
                    type="button"
                    class="rounded-lg border border-red-200 px-3 py-1.5 text-sm font-medium text-red-600 transition hover:bg-red-50"
                    @click="emit('delete', organization.id)"
                >
                    Удалить
                </button>
            </div>
        </div>
    </div>
</template>