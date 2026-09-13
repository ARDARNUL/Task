<script setup>
import { computed } from 'vue';

const props = defineProps({
    currentPage: { type: Number, required: true },
    lastPage: { type: Number, required: true },
});

const emit = defineEmits(['change']);

const pages = computed(() => {
    const total = props.lastPage;
    const current = props.currentPage;
    const windowSize = 2;

    const result = new Set([1, total, current]);
    for (let i = 1; i <= windowSize; i++) {
        if (current - i >= 1) result.add(current - i);
        if (current + i <= total) result.add(current + i);
    }

    const sorted = [...result].sort((a, b) => a - b);
    const withGaps = [];
    let prev = 0;

    for (const p of sorted) {
        if (p - prev > 1 && prev !== 0) {
            withGaps.push('…');
        }
        withGaps.push(p);
        prev = p;
    }

    return withGaps;
});

function go(page) {
    if (page === '…' || page === props.currentPage) return;
    if (page < 1 || page > props.lastPage) return;
    emit('change', page);
}
</script>

<template>
    <nav
        v-if="lastPage > 1"
        class="flex flex-wrap items-center justify-center gap-1"
        aria-label="Пагинация"
    >
        <button
            type="button"
            :disabled="currentPage <= 1"
            class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
            @click="go(currentPage - 1)"
        >
            ←
        </button>

        <template v-for="(p, idx) in pages" :key="`${p}-${idx}`">
            <span
                v-if="p === '…'"
                class="px-2 text-slate-400"
            >
                {{ p }}
            </span>
            <button
                v-else
                type="button"
                :class="[
                    'min-w-[36px] rounded-lg border px-3 py-1.5 text-sm font-medium transition',
                    p === currentPage
                        ? 'border-indigo-600 bg-indigo-600 text-white'
                        : 'border-slate-300 text-slate-700 hover:bg-slate-50',
                ]"
                @click="go(p)"
            >
                {{ p }}
            </button>
        </template>

        <button
            type="button"
            :disabled="currentPage >= lastPage"
            class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
            @click="go(currentPage + 1)"
        >
            →
        </button>
    </nav>
</template>