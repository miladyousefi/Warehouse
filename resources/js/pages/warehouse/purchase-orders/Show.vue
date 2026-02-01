<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, PackageCheck } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import AppPageContent from '@/components/AppPageContent.vue';
import { useI18n } from 'vue-i18n';
import { usePermission } from '@/composables/usePermission';
import { index, receive } from '@/actions/App/Http/Controllers/Warehouse/PurchaseOrderController';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
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
function doReceive() {
    if (confirm(t('purchaseOrders.receive') + '?')) router.get(receive.url({ purchase_order: props.order.id }));
}
</script>

<template>
    <Head :title="String(props.order.order_number)" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="flex flex-row items-center justify-between gap-4 p-4 md:p-6 pb-0">
                    <div>
                        <h1 class="text-xl font-semibold">{{ props.order.order_number }}</h1>
                        <p class="text-sm text-muted-foreground">{{ props.order.supplier?.name }} · {{ (props.order.warehouse as any)?.[locale] }}</p>
                    </div>
                    <div class="flex gap-2">
                        <Badge>{{ props.order.status }}</Badge>
                        <Button v-if="can('purchase_orders.edit') && ['draft','sent','partial'].includes(String(props.order.status))" @click="doReceive">
                            <PackageCheck class="mr-2 h-4 w-4" />{{ t('purchaseOrders.receive') }}
                        </Button>
                        <Link :href="index.url()"><Button variant="outline"><ArrowLeft class="mr-2 h-4 w-4" />{{ t('common.back') }}</Button></Link>
                    </div>
                </div>
            </template>
            <div class="p-4 md:p-6 pt-4 overflow-y-auto">
                <Table class="bg-transparent">
                        <TableHeader>
                            <TableRow class="border-transparent hover:bg-transparent">
                                <TableHead class="text-muted-foreground">{{ t('stock.product') }}</TableHead>
                                <TableHead class="text-muted-foreground">{{ t('common.quantity') }}</TableHead>
                                <TableHead class="text-muted-foreground">{{ t('stockMovements.unitCost') }}</TableHead>
                                <TableHead class="text-muted-foreground">{{ t('purchaseOrders.total') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="item in ((props.order as any).items || [])" :key="(item as any).id" class="border-transparent hover:bg-transparent">
                                <TableCell>{{ (item as any).product?.[locale] ?? '-' }}</TableCell>
                                <TableCell>{{ (item as any).quantity }}</TableCell>
                                <TableCell>{{ (item as any).unit_price }}</TableCell>
                                <TableCell>{{ (item as any).total }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                <p class="mt-4 font-medium">{{ t('purchaseOrders.total') }}: {{ props.order.total }}</p>
            </div>
        </AppPageContent>
    </AppLayout>
</template>
