<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { TrendingDown, DollarSign } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import AppPageContent from '@/components/AppPageContent.vue';
import { useI18n } from 'vue-i18n';
import { index, stockValuation, lowStock } from '@/actions/App/Http/Controllers/Warehouse/ReportController';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';

const { t } = useI18n();
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.reports'), href: index.url() }];
</script>

<template>
    <Head :title="t('reports.title')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="p-4 md:p-6 pb-0">
                    <h1 class="text-xl font-semibold">{{ t('reports.title') }}</h1>
                    <p class="text-sm text-muted-foreground">{{ t('common.view') }}</p>
                </div>
            </template>
            <div class="p-4 md:p-6 pt-4 overflow-y-auto">
                <div class="grid gap-4 md:grid-cols-2">
                    <Link :href="stockValuation.url()" class="flex items-center justify-between rounded-lg border border-transparent bg-transparent p-4 transition-colors hover:bg-muted/30">
                        <div class="flex items-center gap-3">
                            <DollarSign class="h-5 w-5 text-muted-foreground" />
                            <span class="font-medium">{{ t('reports.stockValuation') }}</span>
                        </div>
                        <Button variant="outline" size="sm">{{ t('common.view') }}</Button>
                    </Link>
                    <Link :href="lowStock.url()" class="flex items-center justify-between rounded-lg border border-transparent bg-transparent p-4 transition-colors hover:bg-muted/30">
                        <div class="flex items-center gap-3">
                            <TrendingDown class="h-5 w-5 text-muted-foreground" />
                            <span class="font-medium">{{ t('reports.lowStock') }}</span>
                        </div>
                        <Button variant="outline" size="sm">{{ t('common.view') }}</Button>
                    </Link>
                </div>
            </div>
        </AppPageContent>
    </AppLayout>
</template>
