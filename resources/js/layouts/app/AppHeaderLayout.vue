<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppContent from '@/components/AppContent.vue';
import AppHeader from '@/components/AppHeader.vue';
import AppShell from '@/components/AppShell.vue';
import WarmBackdrop from '@/components/WarmBackdrop.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell class="flex-col">
        <Head>
            <link rel="preconnect" href="https://fonts.googleapis.com" />
            <link
                rel="preconnect"
                href="https://fonts.gstatic.com"
                crossorigin
            />
            <link
                href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,400;9..144,500;9..144,600;9..144,700&family=DM+Sans:wght@300;400;500;600&display=swap"
                rel="stylesheet"
            />
        </Head>
        <AppHeader :breadcrumbs="breadcrumbs" />
        <AppContent>
            <WarmBackdrop>
                <slot />
            </WarmBackdrop>
        </AppContent>
    </AppShell>
</template>
