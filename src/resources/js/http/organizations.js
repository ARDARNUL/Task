import client from './client';

export async function list() {
    const { data } = await client.get('/organizations');
    return data.data;
}

export async function create(yandexUrl) {
    const { data } = await client.post('/organizations', {
        yandex_url: yandexUrl,
    });
    return data.data;
}

export async function show(id) {
    const { data } = await client.get(`/organizations/${id}`);
    return data;
}

export async function sync(id) {
    const { data } = await client.post(`/organizations/${id}/sync`);
    return data;
}

export async function reviews(organizationId, page = 1) {
    const { data } = await client.get(
        `/organizations/${organizationId}/reviews`,
        { params: { page } }
    );
    return data;
}