<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div
    data-slot="card"
    :class="
      cn(
        'text-card-foreground flex flex-col gap-6 rounded-md border border-white/30 border-l-4 border-l-primary/35 bg-white/25 py-6 shadow-[0_0_0_1px_rgba(0,0,0,0.02),0_0_18px_rgba(0,0,0,0.05)] backdrop-blur-xl supports-[backdrop-filter]:bg-white/15 transition-[background-color] hover:bg-white/30 dark:border-white/12 dark:border-l-primary/45 dark:bg-white/4 dark:shadow-[0_0_0_1px_rgba(255,255,255,0.04),0_0_20px_rgba(0,0,0,0.25)] dark:supports-[backdrop-filter]:bg-white/3 dark:hover:bg-white/6',
        'divide-y divide-white/20 dark:divide-white/10',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
