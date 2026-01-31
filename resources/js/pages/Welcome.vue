<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { dashboard, login, register } from '@/routes';
import {
    Package,
    BarChart3,
    ArrowRightLeft,
    Truck,
    Boxes,
    ClipboardList,
} from 'lucide-vue-next';

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
</script>

<template>
    <Head :title="'The Hunger - ' + t('home.title')">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div
        class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <header
            class="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl"
        >
            <nav class="flex items-center justify-end gap-4">
                <Link
                    v-if="$page.props.auth?.user"
                    :href="dashboard().url"
                    class="inline-block rounded-lg border border-[#19140035] bg-amber-500/10 px-5 py-2 text-sm font-medium text-[#1b1b18] hover:bg-amber-500/20 dark:border-amber-600/30 dark:text-[#EDEDEC] dark:hover:bg-amber-500/20"
                >
                    {{ t('nav.dashboard') }}
                </Link>
                <template v-else>
                    <Link
                        :href="login().url"
                        class="inline-block rounded-lg border border-amber-600/50 bg-amber-500 px-5 py-2 text-sm font-medium text-white hover:bg-amber-600 dark:bg-amber-600 dark:hover:bg-amber-700"
                    >
                        {{ t('auth.login') }}
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="register().url"
                        class="inline-block rounded-lg border border-[#19140035] px-5 py-2 text-sm leading-normal hover:border-amber-500/50 dark:border-[#3E3E3A] dark:hover:border-amber-500/50"
                    >
                        {{ t('auth.signUp') }}
                    </Link>
                </template>
            </nav>
        </header>

        <main class="flex w-full max-w-4xl flex-col items-center gap-10 lg:gap-14">
            <div class="text-center">
                <h1 class="mb-2 text-4xl font-bold tracking-tight text-amber-700 dark:text-amber-400 lg:text-5xl">
                    The Hunger
                </h1>
                <p class="text-lg text-muted-foreground">
                    {{ t('home.subtitle') }}
                </p>
            </div>

            <div class="grid w-full gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    class="flex flex-col gap-3 rounded-xl border border-[#19140020] bg-white p-5 shadow-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
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
                    class="flex flex-col gap-3 rounded-xl border border-[#19140020] bg-white p-5 shadow-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
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
                    class="flex flex-col gap-3 rounded-xl border border-[#19140020] bg-white p-5 shadow-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
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
                    class="flex flex-col gap-3 rounded-xl border border-[#19140020] bg-white p-5 shadow-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
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
                    class="flex flex-col gap-3 rounded-xl border border-[#19140020] bg-white p-5 shadow-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
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
                    class="flex flex-col gap-3 rounded-xl border border-[#19140020] bg-white p-5 shadow-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
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

            <div
                v-if="demoLogin && !$page.props.auth?.user"
                class="w-full max-w-md rounded-xl border border-dashed border-amber-500/40 bg-amber-50/50 p-5 dark:bg-amber-950/20"
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
