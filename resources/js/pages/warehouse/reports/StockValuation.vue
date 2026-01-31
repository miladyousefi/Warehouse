<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { index } from '@/actions/App/Http/Controllers/Warehouse/ReportController';
import { type BreadcrumbItem } from '@/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const props = defineProps<{ items: Array<Record<string, unknown>>; totalValue: number }>();
const { t } = useI18n();
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.reports'), href: index.url() }, { title: t('reports.stockValuation') }];
</script>

<template>
    <Head :title="t('reports.stockValuation')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('reports.stockValuation') }}</CardTitle>
                    <CardDescription>{{ t('reports.totalValue') }}: {{ Number(props.totalValue).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{{ t('stock.warehouse') }}</TableHead>
                                <TableHead>{{ t('stock.product') }}</TableHead>
                                <TableHead>{{ t('common.quantity') }}</TableHead>
                                <TableHead>{{ t('stockMovements.unitCost') }}</TableHead>
                                <TableHead>{{ t('reports.totalValue') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(row, i) in props.items" :key="i">
                                <TableCell>{{ (row as any).warehouse }}</TableCell>
                                <TableCell>{{ (row as any).product }}</TableCell>
                                <TableCell>{{ (row as any).quantity }} {{ (row as any).unit }}</TableCell>
                                <TableCell>{{ (row as any).unit_cost }}</TableCell>
                                <TableCell>{{ Number((row as any).total_value).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
