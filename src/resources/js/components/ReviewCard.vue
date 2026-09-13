<script setup>
defineProps({
    review: { type: Object, required: true },
});

const dateFormatted = (iso) => {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleDateString('ru-RU', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
};

const stars = (rating) => {
    if (!rating) return '';
    return '★'.repeat(rating) + '☆'.repeat(5 - rating);
};
</script>

<template>
    <article class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
        <header class="flex items-start justify-between gap-4">
            <div>
                <div class="font-medium text-slate-900">
                    {{ review.author_name || 'Аноним' }}
                </div>
                <div class="mt-0.5 text-xs text-slate-500">
                    {{ dateFormatted(review.published_at) }}
                </div>
            </div>
            <div
                v-if="review.rating"
                class="shrink-0 text-lg tracking-tight text-amber-500"
                :title="`Оценка: ${review.rating} из 5`"
            >
                {{ stars(review.rating) }}
            </div>
        </header>

        <p
            v-if="review.text"
            class="mt-4 whitespace-pre-line text-sm leading-relaxed text-slate-700"
        >
            {{ review.text }}
        </p>
        <p v-else class="mt-4 text-sm italic text-slate-400">
            Без текста
        </p>
    </article>
</template>