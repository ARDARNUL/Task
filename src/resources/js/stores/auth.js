import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import * as authApi from '../http/auth';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const loading = ref(false);
    const error = ref(null);
    const initialized = ref(false);

    const isAuthenticated = computed(() => user.value !== null);

    async function login(email, password) {
        loading.value = true;
        error.value = null;

        try {
            const data = await authApi.login(email, password);
            user.value = data.user;
            initialized.value = true;
            return true;
        } catch (e) {
            if (e.response?.status === 422) {
                error.value = e.response.data.errors?.email?.[0]
                    ?? 'Неверный email или пароль.';
            } else {
                error.value = 'Не удалось войти. Попробуйте ещё раз.';
            }
            return false;
        } finally {
            loading.value = false;
        }
    }

    async function logout() {
        try {
            await authApi.logout();
        } catch (_) {
            // ignore
        }
        user.value = null;
    }

    async function fetchMe() {
        if (initialized.value) return;

        loading.value = true;
        try {
            const data = await authApi.me();
            user.value = data.user;
        } catch (_) {
            user.value = null;
        } finally {
            initialized.value = true;
            loading.value = false;
        }
    }

    return {
        user,
        loading,
        error,
        initialized,
        isAuthenticated,
        login,
        logout,
        fetchMe,
    };
});