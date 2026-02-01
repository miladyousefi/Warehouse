<script setup lang="ts">
/**
 * Page content with fixed header (title + actions) and scrollable body.
 * Use this so the page title stays visible while only the content scrolls.
 */
</script>

<template>
    <div class="flex flex-1 flex-col min-h-0">
        <slot name="header" />
        <div class="flex-1 min-h-0 overflow-y-auto">
            <slot />
        </div>
    </div>
</template>
