<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{ class?: HTMLAttributes['class'] }>();
</script>

<template>
    <div
        :class="
            cn(
                'relative w-full overflow-x-auto rounded-md border border-white/20 bg-white/10 backdrop-blur-md dark:border-white/10 dark:bg-white/5',
                props.class,
            )
        "
    >
        <table class="w-full min-w-max caption-bottom text-sm">
            <slot />
        </table>
    </div>
</template>
