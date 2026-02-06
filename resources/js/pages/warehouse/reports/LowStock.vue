<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import AppPageContent from '@/components/AppPageContent.vue';
import { useI18n } from 'vue-i18n';
import { index } from '@/actions/App/Http/Controllers/Warehouse/ReportController';
import { type BreadcrumbItem } from '@/types';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { AlertCircle } from 'lucide-vue-next';

const props = defineProps<{ products: Array<{ product: Record<string, unknown>; total_stock: number; min_stock: number }> }>();
const { t } = useI18n();
const locale = computed(() => (useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en'));
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.reports'), href: index.url() }, { title: t('reports.lowStock') }];

// Calculate deficiency for each product
const productsWithDeficiency = computed(() => {
    return props.products.map(row => ({
        ...row,
        deficiency: Math.max(0, row.min_stock - row.total_stock),
        percentage: ((row.total_stock / row.min_stock) * 100).toFixed(1),
    }));
});

// Sorted by deficiency (highest first)
const sortedProducts = computed(() => {
    return [...productsWithDeficiency.value].sort((a, b) => b.deficiency - a.deficiency);
});

</script>

<template>
    <Head :title="t('reports.lowStock')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="p-4 md:p-6 pb-0">
                    <h1 class="text-xl font-semibold">{{ t('reports.lowStock') }}</h1>
                    <p class="text-sm text-muted-foreground">Products below minimum stock threshold</p>
                </div>
            </template>
            <div class="p-4 md:p-6 pt-4">
                <div class="mb-6">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">
                                {{ t('stock.lowStock') }}
                            </CardTitle>
                            <AlertCircle class="h-4 w-4 text-destructive" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold text-destructive">{{ props.products?.length || 0 }}</div>
                            <p class="text-xs text-muted-foreground mt-1">
                                Products requiring restock
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div v-if="sortedProducts?.length" class="rounded-lg border overflow-hidden">
                    <Table class="bg-transparent">
                        <TableHeader>
                            <TableRow class="border-transparent hover:bg-transparent">
                                <TableHead class="text-muted-foreground">{{ t('stock.product') }}</TableHead>
                                <TableHead class="text-muted-foreground text-right">{{ t('common.quantity') }}</TableHead>
                                <TableHead class="text-muted-foreground text-right">{{ t('products.minStock') }}</TableHead>
                                <TableHead class="text-muted-foreground text-right">Needed</TableHead>
                                <TableHead class="text-muted-foreground text-right">Stock Level</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(row, i) in sortedProducts" :key="i" class="border-transparent hover:bg-muted/30">
                                <TableCell class="font-medium">{{ (row.product as any)[locale] }}</TableCell>
                                <TableCell class="text-right">
                                    <Badge variant="destructive">{{ row.total_stock }}</Badge>
                                </TableCell>
                                <TableCell class="text-right">{{ row.min_stock }}</TableCell>
                                <TableCell class="text-right font-medium text-destructive">{{ row.deficiency }}</TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center gap-2 justify-end">
                                        <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                                            <div 
                                                class="h-full bg-red-500" 
                                                :style="{ width: Math.min(100, parseFloat(row.percentage) * 100 / 100) + '%' }"
                                            ></div>
                                        </div>
                                        <span class="text-xs text-muted-foreground w-10">{{ row.percentage }}%</span>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <p v-else class="text-center text-muted-foreground py-8">
                    {{ t('stock.inStock') }}
                </p>
            </div>
        </AppPageContent>
    </AppLayout>
</template>
