<script setup lang="ts">
/**
 * Page content with fixed header (title + actions) and scrollable body.
 * Use this so the page title stays visible while only the content scrolls.
 */
</script>

<template>
    <div class="flex min-h-0 flex-1 flex-col">
        <slot name="header" />
        <div class="min-h-0 flex-1 overflow-y-auto">
            <slot />
        </div>
    </div>
</template>
