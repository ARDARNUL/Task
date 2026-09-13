import client from './client';

export async function login(email, password) {
    const { data } = await client.post('/login', { email, password });
    return data;
}

export async function logout() {
    const { data } = await client.post('/logout');
    return data;
}

export async function me() {
    const { data } = await client.get('/me');
    return data;
}