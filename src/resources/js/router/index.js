import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import Login from '../views/Login.vue';
import Settings from '../views/Settings.vue';
import Organization from '../views/Organization.vue';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: Login,
        meta: { guest: true },
    },
    {
        path: '/',
        redirect: '/settings',
    },
    {
        path: '/settings',
        name: 'settings',
        component: Settings,
        meta: { auth: true },
    },
    {
        path: '/organizations/:id',
        name: 'organization',
        component: Organization,
        props: true,
        meta: { auth: true },
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/settings',
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (!auth.initialized) {
        await auth.fetchMe();
    }

    if (to.meta.auth && !auth.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.guest && auth.isAuthenticated) {
        return { name: 'settings' };
    }

    return true;
});

window.addEventListener('auth:unauthorized', () => {
    const auth = useAuthStore();
    auth.user = null;
    auth.initialized = true;
    router.push({ name: 'login' });
});

export default router;