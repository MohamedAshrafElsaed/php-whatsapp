<script setup lang="ts">
import { inject, computed } from 'vue';

const props = defineProps<{
    value: string;
}>();

const activeTab = inject<any>('activeTab');
const setActiveTab = inject<any>('setActiveTab');

const isActive = computed(() => activeTab.value === props.value);
</script>

<template>
    <button
        type="button"
        @click="setActiveTab(value)"
        :class="[
            'inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium transition-all',
            isActive
                ? 'bg-background text-foreground shadow-sm'
                : 'hover:bg-background/50',
        ]"
    >
        <slot />
    </button>
</template>
