<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { index } from '@/actions/App/Http/Controllers/Warehouse/ReportController';
import { type BreadcrumbItem } from '@/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';

const props = defineProps<{ products: Array<{ product: Record<string, unknown>; total_stock: number; min_stock: number }> }>();
const { t } = useI18n();
const locale = computed(() => (useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en'));
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.reports'), href: index.url() }, { title: t('reports.lowStock') }];
</script>

<template>
    <Head :title="t('reports.lowStock')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('reports.lowStock') }}</CardTitle>
                    <CardDescription>{{ t('stock.lowStock') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{{ t('stock.product') }}</TableHead>
                                <TableHead>{{ t('common.quantity') }}</TableHead>
                                <TableHead>{{ t('products.minStock') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(row, i) in props.products" :key="i">
                                <TableCell class="font-medium">{{ (row.product as any)[locale] }}</TableCell>
                                <TableCell><Badge variant="destructive">{{ row.total_stock }}</Badge></TableCell>
                                <TableCell>{{ row.min_stock }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <p v-if="!props.products?.length" class="text-muted-foreground">{{ t('stock.inStock') }}</p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
