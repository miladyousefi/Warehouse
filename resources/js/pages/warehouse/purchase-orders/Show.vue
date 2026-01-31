<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, PackageCheck } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { usePermission } from '@/composables/usePermission';
import { index } from '@/actions/App/Http/Controllers/Warehouse/PurchaseOrderController';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const props = defineProps<{ order: Record<string, unknown> }>();
const { t } = useI18n();
const { can } = usePermission();
const locale = computed(() => (useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en'));
const breadcrumbs: BreadcrumbItem[] = [
    { title: t('nav.purchaseOrders'), href: index.url() },
    { title: String(props.order.order_number) },
];
function receive() {
    if (confirm(t('purchaseOrders.receive') + '?')) router.post(`/warehouse/purchase-orders/${props.order.id}/receive`);
}
</script>

<template>
    <Head :title="String(props.order.order_number)" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle>{{ order.order_number }}</CardTitle>
                        <CardDescription>{{ order.supplier?.name }} · {{ order.warehouse?.[locale] }}</CardDescription>
                    </div>
                    <div class="flex gap-2">
                        <Badge>{{ props.order.status }}</Badge>
                        <Button v-if="can('purchase_orders.edit') && ['draft','sent','partial'].includes(String(props.order.status))" @click="receive">
                            <PackageCheck class="mr-2 h-4 w-4" />{{ t('purchaseOrders.receive') }}
                        </Button>
                        <Link :href="index.url()"><Button variant="outline"><ArrowLeft class="mr-2 h-4 w-4" />{{ t('common.back') }}</Button></Link>
                    </div>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{{ t('stock.product') }}</TableHead>
                                <TableHead>{{ t('common.quantity') }}</TableHead>
                                <TableHead>{{ t('stockMovements.unitCost') }}</TableHead>
                                <TableHead>{{ t('purchaseOrders.total') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="item in ((props.order as any).items || [])" :key="(item as any).id">
                                <TableCell>{{ (item as any).product?.[locale] ?? '-' }}</TableCell>
                                <TableCell>{{ (item as any).quantity }}</TableCell>
                                <TableCell>{{ (item as any).unit_price }}</TableCell>
                                <TableCell>{{ (item as any).total }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <p class="mt-4 font-medium">{{ t('purchaseOrders.total') }}: {{ props.order.total }}</p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
