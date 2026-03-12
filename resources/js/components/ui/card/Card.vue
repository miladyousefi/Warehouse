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
        'bg-card/80 text-card-foreground flex flex-col gap-6 rounded-xl border border-border/60 py-6 shadow-sm backdrop-blur supports-[backdrop-filter]:bg-card/70 transition-shadow',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
