<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import AppPageContent from '@/components/AppPageContent.vue';
import { useI18n } from 'vue-i18n';
import { index } from '@/actions/App/Http/Controllers/Warehouse/ReportController';
import { type BreadcrumbItem } from '@/types';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const props = defineProps<{ items: Array<Record<string, unknown>>; totalValue: number }>();
const { t } = useI18n();
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.reports'), href: index.url() }, { title: t('reports.stockValuation') }];
</script>

<template>
    <Head :title="t('reports.stockValuation')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="p-4 md:p-6 pb-0">
                    <h1 class="text-xl font-semibold">{{ t('reports.stockValuation') }}</h1>
                    <p class="text-sm text-muted-foreground">{{ t('reports.totalValue') }}: {{ Number(props.totalValue).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</p>
                </div>
            </template>
            <div class="p-4 md:p-6 pt-4 overflow-y-auto">
                <Table class="bg-transparent">
                    <TableHeader>
                        <TableRow class="border-transparent hover:bg-transparent">
                            <TableHead class="text-muted-foreground">{{ t('stock.warehouse') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('stock.product') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('common.quantity') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('stockMovements.unitCost') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('reports.totalValue') }}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(row, i) in props.items" :key="i" class="border-transparent hover:bg-transparent">
                            <TableCell>{{ (row as any).warehouse }}</TableCell>
                            <TableCell>{{ (row as any).product }}</TableCell>
                            <TableCell>{{ (row as any).quantity }} {{ (row as any).unit }}</TableCell>
                            <TableCell>{{ (row as any).unit_cost }}</TableCell>
                            <TableCell>{{ Number((row as any).total_value).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </AppPageContent>
    </AppLayout>
</template>
