<script setup>
import { computed } from 'vue';

const props = defineProps({
    size: { type: String, default: 'md' }, 
});

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'xs':
            return 'h-3 w-3';
        case 'sm':
            return 'h-4 w-4';
        case 'md':
            return 'h-6 w-6';
        case 'lg':
            return 'h-10 w-10';
        case 'xl':
            return 'h-16 w-16';
        default:
            return 'h-6 w-6';
    }
});
</script>

<template>
    <svg
        :class="['animate-spin', sizeClasses]"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        aria-hidden="true"
    >
        <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
        />
        <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
        />
    </svg>
</template>