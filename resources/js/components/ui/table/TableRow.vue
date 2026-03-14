<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{ class?: HTMLAttributes['class'] }>();
</script>

<template>
    <tr
        :class="
            cn(
                'border-b transition-colors hover:bg-primary/5 data-[state=selected]:bg-primary/10 dark:hover:bg-primary/10 dark:data-[state=selected]:bg-primary/20',
                props.class,
            )
        "
    >
        <slot />
    </tr>
</template>
