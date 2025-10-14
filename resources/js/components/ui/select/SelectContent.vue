<script lang="ts" setup>
import {
    SelectContent,
    type SelectContentEmits,
    type SelectContentProps,
    SelectPortal,
    SelectViewport,
    useForwardPropsEmits
} from 'reka-ui';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<SelectContentProps & { class?: string }>(),
    {
        position: 'popper',
        sideOffset: 4
    }
);

const emits = defineEmits<SelectContentEmits>();

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <SelectPortal>
        <SelectContent
            :class="cn(
                'data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 relative z-50 max-h-96 min-w-32 overflow-hidden rounded-lg border bg-popover text-popover-foreground shadow-md',
                props.position === 'popper' &&
                    'data-[side=left]:-translate-x-1 data-[side=top]:-translate-y-1 data-[side=right]:translate-x-1 data-[side=bottom]:translate-y-1',
                props.class,
            )"
            v-bind="forwarded"
        >
            <SelectViewport
                :class="cn(
                    'p-1',
                    props.position === 'popper' &&
                        'h-[--reka-select-trigger-height] w-full min-w-[--reka-select-trigger-width]',
                )"
            >
                <slot />
            </SelectViewport>
        </SelectContent>
    </SelectPortal>
</template>
