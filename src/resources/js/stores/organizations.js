import { defineStore } from 'pinia';
import { ref } from 'vue';
import * as orgApi from '../http/organizations';

export const useOrganizationsStore = defineStore('organizations', () => {
    const items = ref([]);
    const loading = ref(false);
    const error = ref(null);

    let pollTimer = null;

    async function fetchAll() {
        loading.value = true;
        error.value = null;
        try {
            items.value = await orgApi.list();
        } catch (e) {
            error.value = 'Не удалось загрузить список организаций.';
        } finally {
            loading.value = false;
        }
    }

    async function create(yandexUrl) {
        error.value = null;
        try {
            const org = await orgApi.create(yandexUrl);
            // Обновляем или добавляем в список
            const idx = items.value.findIndex((o) => o.id === org.id);
            if (idx >= 0) items.value[idx] = org;
            else items.value.unshift(org);

            startPolling();
            return org;
        } catch (e) {
            if (e.response?.status === 422) {
                error.value =
                    e.response.data.errors?.yandex_url?.[0] ??
                    'Некорректная ссылка.';
            } else {
                error.value = 'Не удалось добавить организацию.';
            }
            return null;
        }
    }

    async function sync(id) {
        try {
            await orgApi.sync(id);
            const idx = items.value.findIndex((o) => o.id === id);
            if (idx >= 0) {
                items.value[idx] = { ...items.value[idx], parse_status: 'pending' };
            }
            startPolling();
        } catch (_) {}
    }

    async function refreshOne(id) {
        try {
            const data = await orgApi.show(id);
            const org = data.organization;
            const idx = items.value.findIndex((o) => o.id === org.id);
            if (idx >= 0) items.value[idx] = org;
            else items.value.unshift(org);
        } catch (_) {}
    }

    /**
     * Запускает поллинг: пока есть организации в статусе pending/running,
     * каждые 3 секунды дёргает GET /api/organizations.
     */
    function startPolling() {
        stopPolling();

        const tick = async () => {
            const hasActive = items.value.some(
                (o) => o.parse_status === 'pending' || o.parse_status === 'running'
            );

            if (!hasActive) {
                stopPolling();
                return;
            }

            try {
                items.value = await orgApi.list();
            } catch (_) {}

            pollTimer = setTimeout(tick, 3000);
        };

        pollTimer = setTimeout(tick, 2000);
    }

    function stopPolling() {
        if (pollTimer) {
            clearTimeout(pollTimer);
            pollTimer = null;
        }
    }

    return {
        items,
        loading,
        error,
        fetchAll,
        create,
        sync,
        refreshOne,
        startPolling,
        stopPolling,
    };
});