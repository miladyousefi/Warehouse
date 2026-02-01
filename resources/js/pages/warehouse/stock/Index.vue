<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import AppPageContent from '@/components/AppPageContent.vue';
import Pagination from '@/components/Pagination.vue';
import { useI18n } from 'vue-i18n';
import { index } from '@/actions/App/Http/Controllers/Warehouse/StockController';
import { type BreadcrumbItem } from '@/types';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const props = defineProps<{ balances: { data: Array<Record<string, unknown>>; links: Array<{ url: string | null; label: string }> }; warehouses: Array<Record<string, unknown>> }>();
const { t } = useI18n();
const locale = computed(() => (useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en'));
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.stock'), href: index.url() }];
</script>

<template>
    <Head :title="t('stock.title')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="p-4 md:p-6 pb-0">
                    <h1 class="text-xl font-semibold">{{ t('stock.title') }}</h1>
                    <p class="text-sm text-muted-foreground">{{ t('common.view') }}</p>
                </div>
            </template>
            <div class="p-4 md:p-6 pt-4 overflow-y-auto">
                <Table class="bg-transparent">
                    <TableHeader>
                        <TableRow class="border-b border-border hover:bg-muted/30">
                            <TableHead class="text-muted-foreground">{{ t('stock.warehouse') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('stock.product') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('common.quantity') }}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="b in balances.data" :key="(b as any).id" class="border-b border-border hover:bg-muted/30">
                            <TableCell>{{ (b as any).warehouse?.[locale] ?? '-' }}</TableCell>
                            <TableCell>
                                <Link v-if="(b as any).product_id" :href="`/warehouse/products/${(b as any).product_id}`" class="hover:underline">{{ (b as any).product?.[locale] ?? '-' }}</Link>
                                <span v-else>{{ (b as any).product?.[locale] ?? '-' }}</span>
                            </TableCell>
                            <TableCell>{{ (b as any).quantity }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <Pagination v-if="balances.links?.length" :links="balances.links" class="mt-4" />
            </div>
        </AppPageContent>
    </AppLayout>
</template>
