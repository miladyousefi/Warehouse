<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { CheckCircle2, AlertCircle, X } from 'lucide-vue-next';
import { computed, ref, watch, onUnmounted } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import NewOrderNotifier from '@/components/NewOrderNotifier.vue';
import OrderCancelledNotifier from '@/components/OrderCancelledNotifier.vue';
import { Alert, AlertTitle } from '@/components/ui/alert';
import WaiterCallNotifier from '@/components/WaiterCallNotifier.vue';
import WarmBackdrop from '@/components/WarmBackdrop.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const successMessage = computed(() => page.props.flash?.success as string);
const errorMessage = computed(() => page.props.flash?.error as string);

const flashKey = computed(() => `${successMessage.value || ''}|${errorMessage.value || ''}`);

const showFlash = ref(true);
const hideTimer = ref<number | null>(null);
const dismissedFlashKey = ref<string | null>(null);

const closeButtonClass = computed(() => {
    if (errorMessage.value) {
        return 'text-rose-700 hover:bg-rose-500/10 dark:text-rose-300';
    }
    if (successMessage.value) {
        return 'text-emerald-700 hover:bg-emerald-500/10 dark:text-emerald-300';
    }
    return 'text-muted-foreground hover:bg-accent';
});

function clearHideTimer() {
    if (!hideTimer.value) return;
    window.clearTimeout(hideTimer.value);
    hideTimer.value = null;
}

function closeFlash() {
    dismissedFlashKey.value = flashKey.value;
    showFlash.value = false;
    clearHideTimer();
}

watch(flashKey, (key, oldKey) => {
    if (!key || key === '|') {
        showFlash.value = false;
        dismissedFlashKey.value = null;
        clearHideTimer();
        return;
    }

    // Only re-open when the message actually changes.
    if (key !== oldKey) {
        dismissedFlashKey.value = null;
        showFlash.value = true;
        clearHideTimer();
    }

    const hasSuccess = Boolean(successMessage.value);
    const hasError = Boolean(errorMessage.value);

    // Auto close: keep errors longer.
    const delayMs = hasError ? 9000 : hasSuccess ? 4500 : 0;
    if (delayMs > 0) {
        hideTimer.value = window.setTimeout(() => {
            showFlash.value = false;
            hideTimer.value = null;
        }, delayMs);
    }
});

onUnmounted(() => {
    clearHideTimer();
});
</script>

<template>
    <AppShell variant="sidebar">
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
        <AppSidebar />
        <AppContent variant="sidebar" class="flex flex-col overflow-hidden">
            <WarmBackdrop class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <NewOrderNotifier />
                <OrderCancelledNotifier />
                <WaiterCallNotifier />
                <AppSidebarHeader
                    :breadcrumbs="breadcrumbs"
                    class="flex-shrink-0"
                />
                <div
                    v-if="
                        showFlash &&
                        flashKey !== dismissedFlashKey &&
                        (successMessage || errorMessage)
                    "
                    class="px-6 pt-2 md:px-4"
                >
                    <div class="relative">
                        <button
                            type="button"
                            class="absolute right-2 top-2 inline-flex h-8 w-8 items-center justify-center rounded-md"
                            :class="closeButtonClass"
                            @click="closeFlash"
                            title="Close"
                        >
                            <X class="h-4 w-4" />
                        </button>

                        <Alert
                            v-if="successMessage"
                            variant="default"
                            class="border border-emerald-500/50 bg-transparent text-emerald-700 dark:text-emerald-300"
                        >
                            <CheckCircle2 class="h-4 w-4" />
                            <AlertTitle>{{ successMessage }}</AlertTitle>
                        </Alert>
                        <Alert
                            v-if="errorMessage"
                            variant="destructive"
                            class="border border-rose-500/50 bg-transparent text-rose-700 dark:text-rose-300"
                        >
                            <AlertCircle class="h-4 w-4" />
                            <AlertTitle>{{ errorMessage }}</AlertTitle>
                        </Alert>
                    </div>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto">
                    <slot />
                </div>
            </WarmBackdrop>
        </AppContent>
    </AppShell>
</template>
