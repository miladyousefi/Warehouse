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

const props = defineProps<{ products: Array<{ product: Record<string, unknown>; total_stock: number; min_stock: number }> }>();
const { t } = useI18n();
const locale = computed(() => (useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en'));
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.reports'), href: index.url() }, { title: t('reports.lowStock') }];
</script>

<template>
    <Head :title="t('reports.lowStock')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="p-4 md:p-6 pb-0">
                    <h1 class="text-xl font-semibold">{{ t('reports.lowStock') }}</h1>
                    <p class="text-sm text-muted-foreground">{{ t('stock.lowStock') }}</p>
                </div>
            </template>
            <div class="p-4 md:p-6 pt-4 overflow-y-auto">
                <Table class="bg-transparent">
                    <TableHeader>
                        <TableRow class="border-transparent hover:bg-transparent">
                            <TableHead class="text-muted-foreground">{{ t('stock.product') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('common.quantity') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('products.minStock') }}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(row, i) in props.products" :key="i" class="border-transparent hover:bg-transparent">
                            <TableCell class="font-medium">{{ (row.product as any)[locale] }}</TableCell>
                            <TableCell><Badge variant="destructive">{{ row.total_stock }}</Badge></TableCell>
                            <TableCell>{{ row.min_stock }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <p v-if="!props.products?.length" class="text-muted-foreground py-4">{{ t('stock.inStock') }}</p>
            </div>
        </AppPageContent>
    </AppLayout>
</template>
