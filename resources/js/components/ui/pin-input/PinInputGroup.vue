<script lang="ts" setup>
import type { PrimitiveProps } from 'reka-ui';
import { Primitive, useForwardProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { cn } from '@/lib/utils';

const props = defineProps<PrimitiveProps & { class?: HTMLAttributes['class'] }>();
const delegatedProps = reactiveOmit(props, 'class');
const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <Primitive
        :class="cn('flex items-center', props.class)"
        data-slot="pin-input-group"
        v-bind="forwardedProps"
    >
        <slot />
    </Primitive>
</template>
