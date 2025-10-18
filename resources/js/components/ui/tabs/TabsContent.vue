<script setup lang="ts">
import { inject, computed } from 'vue';

const props = defineProps<{
    value: string;
}>();

const activeTab = inject<any>('activeTab');
const isActive = computed(() => activeTab.value === props.value);
</script>

<template>
    <div v-if="isActive" class="mt-2">
        <slot />
    </div>
</template>
