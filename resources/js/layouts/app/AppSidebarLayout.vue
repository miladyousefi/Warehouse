<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import type { BreadcrumbItem } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CheckCircle2, AlertCircle } from 'lucide-vue-next';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const successMessage = computed(() => page.props.flash?.success as string);
const errorMessage = computed(() => page.props.flash?.error as string);
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-hidden flex flex-col">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" class="flex-shrink-0" />
            <div class="px-4 md:px-6 pt-2 space-y-2" v-if="successMessage || errorMessage">
                <Alert v-if="successMessage" variant="default" class="border-emerald-500/50 bg-emerald-50 text-emerald-900 dark:bg-emerald-950/20 dark:text-emerald-400">
                    <CheckCircle2 class="h-4 w-4" />
                    <AlertTitle>{{ successMessage }}</AlertTitle>
                </Alert>
                <Alert v-if="errorMessage" variant="destructive">
                    <AlertCircle class="h-4 w-4" />
                    <AlertTitle>{{ errorMessage }}</AlertTitle>
                </Alert>
            </div>
            <div class="flex-1 overflow-y-auto">
                <slot />
            </div>
        </AppContent>
    </AppShell>
</template>
