<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Eye } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import AppPageContent from '@/components/AppPageContent.vue';
import { useI18n } from 'vue-i18n';
import { index as productsIndex } from '@/actions/App/Http/Controllers/Warehouse/ProductController';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const props = defineProps<{
    product: {
        id: number;
        name_tr: string;
        name_en: string;
        sku: string | null;
        barcode: string | null;
        category?: { name_tr: string; name_en: string };
        unit?: { symbol: string };
    };
    logs: Array<{
        id: number;
        action: string;
        description: string | null;
        created_at: string;
        user?: { name: string } | null;
    }>;
    stockBalances: Array<{
        id: number;
        quantity: string | number;
        warehouse?: { name_tr: string; name_en: string };
    }>;
}>();

const { t } = useI18n();
const locale = computed(() => (useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en'));
const breadcrumbs: BreadcrumbItem[] = [
    { title: t('nav.products'), href: productsIndex.url() },
    { title: props.product[locale.value] || props.product.name_tr, href: '#' },
];
</script>

<template>
    <Head :title="(product as any)[locale] || product.name_tr" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="flex flex-row items-center justify-between gap-4 p-4 md:p-6 pb-0">
                    <div>
                        <h1 class="text-xl font-semibold">{{ (product as any)[locale] || product.name_tr }}</h1>
                        <p class="text-sm text-muted-foreground">{{ product.sku || product.barcode || t('products.title') }}</p>
                    </div>
                    <Link :href="productsIndex.url()">
                        <Button variant="outline"><ArrowLeft class="mr-2 h-4 w-4" />{{ t('common.back') }}</Button>
                    </Link>
                </div>
            </template>
            <div class="flex flex-1 flex-col gap-6 p-4 md:p-6 pt-4 overflow-y-auto">
                <!-- Current stock by warehouse -->
                <div>
                    <h2 class="text-sm font-medium mb-2">{{ t('products.currentStock') }}</h2>
                    <Table class="bg-transparent">
                        <TableHeader>
                            <TableRow class="border-transparent hover:bg-transparent">
                                <TableHead class="text-muted-foreground">{{ t('stock.warehouse') }}</TableHead>
                                <TableHead class="text-muted-foreground">{{ t('common.quantity') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="b in stockBalances" :key="b.id" class="border-transparent hover:bg-transparent">
                                <TableCell>{{ (b.warehouse as any)?.[locale] ?? '-' }}</TableCell>
                                <TableCell class="font-medium">{{ b.quantity }}</TableCell>
                            </TableRow>
                            <TableRow v-if="!stockBalances.length" class="border-transparent hover:bg-transparent">
                                <TableCell colspan="2" class="text-muted-foreground">{{ t('stock.outOfStock') }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Product activity logs -->
                <div>
                    <h2 class="text-sm font-medium mb-2">{{ t('activityLogs.title') }}</h2>
                    <Table class="bg-transparent">
                        <TableHeader>
                            <TableRow class="border-transparent hover:bg-transparent">
                                <TableHead class="text-muted-foreground">{{ t('common.date') }}</TableHead>
                                <TableHead class="text-muted-foreground">{{ t('activityLogs.action') }}</TableHead>
                                <TableHead class="text-muted-foreground">{{ t('activityLogs.description') }}</TableHead>
                                <TableHead class="text-muted-foreground">{{ t('activityLogs.user') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="log in logs" :key="log.id" class="border-transparent hover:bg-transparent">
                                <TableCell class="whitespace-nowrap text-sm">{{ new Date(log.created_at).toLocaleString() }}</TableCell>
                                <TableCell><Badge variant="secondary">{{ log.action }}</Badge></TableCell>
                                <TableCell class="max-w-xs truncate text-sm">{{ log.description || '-' }}</TableCell>
                                <TableCell class="text-sm">{{ log.user?.name ?? '-' }}</TableCell>
                            </TableRow>
                            <TableRow v-if="!logs.length" class="border-transparent hover:bg-transparent">
                                <TableCell colspan="4" class="text-muted-foreground">{{ t('activityLogs.noLogs') }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>
        </AppPageContent>
    </AppLayout>
</template>
