<script setup lang="ts">
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { index } from '@/actions/App/Http/Controllers/Warehouse/StockController';
import { type BreadcrumbItem } from '@/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const props = defineProps<{ balances: { data: Array<Record<string, unknown>>; links: Array<{ url: string | null; label: string }> }; warehouses: Array<Record<string, unknown>> }>();
const { t } = useI18n();
const locale = computed(() => (useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en'));
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.stock'), href: index.url() }];
</script>

<template>
    <Head :title="t('stock.title')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('stock.title') }}</CardTitle>
                    <CardDescription>{{ t('common.view') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{{ t('stock.warehouse') }}</TableHead>
                                <TableHead>{{ t('stock.product') }}</TableHead>
                                <TableHead>{{ t('common.quantity') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="b in balances.data" :key="(b as any).id">
                                <TableCell>{{ (b as any).warehouse?.[locale] ?? '-' }}</TableCell>
                                <TableCell>{{ (b as any).product?.[locale] ?? '-' }}</TableCell>
                                <TableCell>{{ (b as any).quantity }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div v-if="balances.links?.length" class="mt-4 flex gap-2">
                        <a v-for="(link, i) in balances.links" :key="i" :href="link.url || '#'" class="rounded border px-3 py-1 text-sm" :class="{ 'opacity-50 pointer-events-none': !link.url }">{{ link.label }}</a>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
