<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import AppPageContent from '@/components/AppPageContent.vue';
import { useI18n } from 'vue-i18n';
import { index } from '@/actions/App/Http/Controllers/Warehouse/ReportController';
import { type BreadcrumbItem } from '@/types';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DollarSign } from 'lucide-vue-next';

const props = defineProps<{ items: Array<Record<string, unknown>>; totalValue: number }>();
const { t } = useI18n();
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.reports'), href: index.url() }, { title: t('reports.stockValuation') }];

// Group by warehouse
const groupedByWarehouse = computed(() => {
    const grouped: Record<string, Array<Record<string, unknown>>> = {};
    props.items.forEach(item => {
        const warehouse = (item as any).warehouse || 'Unknown';
        if (!grouped[warehouse]) {
            grouped[warehouse] = [];
        }
        grouped[warehouse].push(item);
    });
    return grouped;
});

// Calculate total per warehouse
const warehouseTotals = computed(() => {
    const totals: Record<string, number> = {};
    props.items.forEach(item => {
        const warehouse = (item as any).warehouse || 'Unknown';
        const value = Number((item as any).total_value) || 0;
        totals[warehouse] = (totals[warehouse] || 0) + value;
    });
    return totals;
});

</script>

<template>
    <Head :title="t('reports.stockValuation')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="p-4 md:p-6 pb-0">
                    <h1 class="text-xl font-semibold">{{ t('reports.stockValuation') }}</h1>
                    <p class="text-sm text-muted-foreground">Detailed inventory valuation report</p>
                </div>
            </template>
            <div class="p-4 md:p-6 pt-4">
                <Card class="mb-6">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ t('reports.totalValue') }}
                        </CardTitle>
                        <DollarSign class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">
                            {{ Number(props.totalValue).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Total inventory value across all warehouses
                        </p>
                    </CardContent>
                </Card>

                <div v-for="(items, warehouse) in groupedByWarehouse" :key="warehouse" class="mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold">{{ warehouse }}</h3>
                            <p class="text-sm text-muted-foreground">
                                Value: {{ Number(warehouseTotals[warehouse]).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                            </p>
                        </div>
                    </div>
                    <div class="rounded-lg border overflow-hidden">
                        <Table class="bg-transparent">
                            <TableHeader>
                                <TableRow class="border-transparent hover:bg-transparent">
                                    <TableHead class="text-muted-foreground">{{ t('stock.product') }}</TableHead>
                                    <TableHead class="text-muted-foreground text-right">{{ t('common.quantity') }}</TableHead>
                                    <TableHead class="text-muted-foreground text-right">{{ t('stockMovements.unitCost') }}</TableHead>
                                    <TableHead class="text-muted-foreground text-right">{{ t('reports.totalValue') }}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(row, i) in items" :key="i" class="border-transparent hover:bg-muted/30">
                                    <TableCell>{{ (row as any).product }}</TableCell>
                                    <TableCell class="text-right">{{ (row as any).quantity }} {{ (row as any).unit }}</TableCell>
                                    <TableCell class="text-right">{{ Number((row as any).unit_cost).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</TableCell>
                                    <TableCell class="text-right font-medium">{{ Number((row as any).total_value).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </div>

                <p v-if="!props.items?.length" class="text-center text-muted-foreground py-8">
                    No stock data available
                </p>
            </div>
        </AppPageContent>
    </AppLayout>
</template>
