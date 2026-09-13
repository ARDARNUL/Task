<script setup>
defineProps({
    id: { type: String, required: true },
});
</script>

<template>
    <div class="min-h-screen bg-slate-100 px-6 py-8">
        <div class="mx-auto max-w-5xl">
            <div class="rounded-2xl bg-white p-8 shadow">
                <h1 class="text-xl font-semibold text-slate-900">
                    Организация #{{ id }}
                </h1>
                <p class="mt-2 text-sm text-slate-500">
                    Здесь будет рейтинг, счётчики и список отзывов с пагинацией.
                </p>
            </div>
        </div>
    </div>
</template>