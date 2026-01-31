<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BarChart3, TrendingDown, DollarSign } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { index, stockValuation, lowStock } from '@/actions/App/Http/Controllers/Warehouse/ReportController';
import { type BreadcrumbItem } from '@/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

const { t } = useI18n();
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.reports'), href: index.url() }];
</script>

<template>
    <Head :title="t('reports.title')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('reports.title') }}</CardTitle>
                    <CardDescription>{{ t('common.view') }}</CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-2">
                    <Link :href="stockValuation.url()">
                        <Card class="cursor-pointer transition-colors hover:bg-muted/50">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium">{{ t('reports.stockValuation') }}</CardTitle>
                                <DollarSign class="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <Button variant="outline">{{ t('common.view') }}</Button>
                            </CardContent>
                        </Card>
                    </Link>
                    <Link :href="lowStock.url()">
                        <Card class="cursor-pointer transition-colors hover:bg-muted/50">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium">{{ t('reports.lowStock') }}</CardTitle>
                                <TrendingDown class="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <Button variant="outline">{{ t('common.view') }}</Button>
                            </CardContent>
                        </Card>
                    </Link>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
