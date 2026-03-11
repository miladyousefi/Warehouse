<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import {
    Package,
    BarChart3,
    ArrowRightLeft,
    Truck,
    Boxes,
    ClipboardList,
} from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import { dashboard, login, logout, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
        demoLogin?: { email: string; password: string };
    }>(),
    {
        canRegister: true,
    },
);

const { t } = useI18n();

const handleLogout = () => {
    router.flushAll();
};
</script>

<template>
    <Head :title="'The Hunger - ' + t('home.title')">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="relative min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(80%_60%_at_50%_0%,rgba(245,158,11,0.18),rgba(253,253,252,0))] dark:bg-[radial-gradient(80%_60%_at_50%_0%,rgba(245,158,11,0.12),rgba(10,10,10,0))]" />

        <header class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-5 lg:px-8">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl border border-black/10 bg-white/70 backdrop-blur dark:border-white/10 dark:bg-white/5">
                    <AppLogoIcon className="h-5 w-5 text-amber-700 dark:text-amber-400" />
                </div>
                <div class="leading-tight">
                    <div class="text-sm font-semibold tracking-wide">The Hunger</div>
                    <div class="text-xs text-muted-foreground">{{ t('home.title') }}</div>
                </div>
            </div>

            <nav class="flex items-center gap-2">
                <Link v-if="$page.props.auth?.user" :href="dashboard().url" class="inline-flex h-9 items-center rounded-lg bg-amber-500 px-4 text-sm font-medium text-white hover:bg-amber-600 dark:bg-amber-600 dark:hover:bg-amber-700">
                    {{ t('nav.dashboard') }}
                </Link>
                <Link
                    v-if="$page.props.auth?.user"
                    class="inline-flex h-9 items-center rounded-lg border border-black/10 bg-white/60 px-4 text-sm font-medium hover:bg-white/80 dark:border-white/10 dark:bg-white/5 dark:hover:bg-white/10"
                    :href="logout()"
                    as="button"
                    @click="handleLogout"
                >
                    {{ t('auth.logout') || 'Log out' }}
                </Link>

                <template v-else>
                    <Link :href="login().url" class="inline-flex h-9 items-center rounded-lg bg-amber-500 px-4 text-sm font-medium text-white hover:bg-amber-600 dark:bg-amber-600 dark:hover:bg-amber-700">
                        {{ t('auth.login') }}
                    </Link>
                    <Link v-if="canRegister" :href="register().url" class="inline-flex h-9 items-center rounded-lg border border-black/10 bg-white/60 px-4 text-sm font-medium hover:bg-white/80 dark:border-white/10 dark:bg-white/5 dark:hover:bg-white/10">
                        {{ t('auth.signUp') }}
                    </Link>
                </template>
            </nav>
        </header>

        <main class="mx-auto flex w-full max-w-6xl flex-col gap-10 px-6 pb-12 pt-4 lg:grid lg:grid-cols-12 lg:gap-10 lg:px-8 lg:pb-20">
            <section class="lg:col-span-5">
                <h1 class="text-4xl font-semibold tracking-tight lg:text-5xl">
                    {{ t('home.subtitle') }}
                </h1>
                <p class="mt-4 text-base text-muted-foreground">
                    {{ t('home.features.productsDesc') }}
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <Link v-if="$page.props.auth?.user" :href="dashboard().url" class="inline-flex h-10 items-center rounded-lg bg-amber-500 px-5 text-sm font-medium text-white hover:bg-amber-600 dark:bg-amber-600 dark:hover:bg-amber-700">
                        {{ t('nav.dashboard') }}
                    </Link>
                    <Link v-else :href="login().url" class="inline-flex h-10 items-center rounded-lg bg-amber-500 px-5 text-sm font-medium text-white hover:bg-amber-600 dark:bg-amber-600 dark:hover:bg-amber-700">
                        {{ t('auth.login') }}
                    </Link>
                    <a href="#features" class="inline-flex h-10 items-center rounded-lg border border-black/10 bg-white/60 px-5 text-sm font-medium hover:bg-white/80 dark:border-white/10 dark:bg-white/5 dark:hover:bg-white/10">
                        {{ t('common.view') || 'View' }} {{ t('home.title') }}
                    </a>
                </div>
            </section>

            <section id="features" class="lg:col-span-7">
                <div class="grid w-full gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    class="flex flex-col gap-3 rounded-xl border border-black/10 bg-white/70 p-5 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/5"
                >
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                        <Package class="h-5 w-5" />
                    </div>
                    <h2 class="font-semibold">{{ t('home.features.products') }}</h2>
                    <p class="text-sm text-muted-foreground">
                        {{ t('home.features.productsDesc') }}
                    </p>
                </div>
                <div
                    class="flex flex-col gap-3 rounded-xl border border-black/10 bg-white/70 p-5 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/5"
                >
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                        <Boxes class="h-5 w-5" />
                    </div>
                    <h2 class="font-semibold">{{ t('home.features.rawMaterials') }}</h2>
                    <p class="text-sm text-muted-foreground">
                        {{ t('home.features.rawMaterialsDesc') }}
                    </p>
                </div>
                <div
                    class="flex flex-col gap-3 rounded-xl border border-black/10 bg-white/70 p-5 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/5"
                >
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                        <ArrowRightLeft class="h-5 w-5" />
                    </div>
                    <h2 class="font-semibold">{{ t('home.features.movements') }}</h2>
                    <p class="text-sm text-muted-foreground">
                        {{ t('home.features.movementsDesc') }}
                    </p>
                </div>
                <div
                    class="flex flex-col gap-3 rounded-xl border border-black/10 bg-white/70 p-5 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/5"
                >
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                        <Truck class="h-5 w-5" />
                    </div>
                    <h2 class="font-semibold">{{ t('home.features.suppliers') }}</h2>
                    <p class="text-sm text-muted-foreground">
                        {{ t('home.features.suppliersDesc') }}
                    </p>
                </div>
                <div
                    class="flex flex-col gap-3 rounded-xl border border-black/10 bg-white/70 p-5 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/5"
                >
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                        <ClipboardList class="h-5 w-5" />
                    </div>
                    <h2 class="font-semibold">{{ t('home.features.purchaseOrders') }}</h2>
                    <p class="text-sm text-muted-foreground">
                        {{ t('home.features.purchaseOrdersDesc') }}
                    </p>
                </div>
                <div
                    class="flex flex-col gap-3 rounded-xl border border-black/10 bg-white/70 p-5 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/5"
                >
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                        <BarChart3 class="h-5 w-5" />
                    </div>
                    <h2 class="font-semibold">{{ t('home.features.reports') }}</h2>
                    <p class="text-sm text-muted-foreground">
                        {{ t('home.features.reportsDesc') }}
                    </p>
                </div>
                </div>
            </section>

            <div
                v-if="demoLogin && !$page.props.auth?.user"
                class="lg:col-span-12 w-full max-w-md rounded-xl border border-dashed border-amber-500/40 bg-amber-50/50 p-5 dark:bg-amber-950/20"
            >
                <h3 class="mb-2 font-medium text-amber-800 dark:text-amber-200">
                    {{ t('auth.demoCredentials') }}
                </h3>
                <p class="mb-3 text-sm text-muted-foreground">
                    {{ t('auth.demoHint') }}
                </p>
                <div class="space-y-1 font-mono text-sm">
                    <p><span class="text-muted-foreground">Email:</span> {{ demoLogin.email }}</p>
                    <p><span class="text-muted-foreground">Password:</span> {{ demoLogin.password }}</p>
                </div>
                <Link :href="login().url" class="mt-4 inline-block">
                    <span class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600">
                        {{ t('auth.login') }} →
                    </span>
                </Link>
            </div>
        </main>
    </div>
</template>
