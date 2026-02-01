<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Eye } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import AppPageContent from '@/components/AppPageContent.vue';
import Pagination from '@/components/Pagination.vue';
import { useI18n } from 'vue-i18n';
import { usePermission } from '@/composables/usePermission';
import { index, create } from '@/actions/App/Http/Controllers/Warehouse/PurchaseOrderController';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const props = defineProps<{ orders: { data: Array<Record<string, unknown>>; links: Array<{ url: string | null; label: string }> } }>();
const { t } = useI18n();
const { can } = usePermission();
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.purchaseOrders'), href: index.url() }];
</script>

<template>
    <Head :title="t('purchaseOrders.title')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="flex flex-row items-center justify-between gap-4 p-4 md:p-6 pb-0">
                    <div>
                        <h1 class="text-xl font-semibold">{{ t('purchaseOrders.title') }}</h1>
                        <p class="text-sm text-muted-foreground">{{ t('common.view') }}</p>
                    </div>
                    <Link v-if="can('purchase_orders.create')" :href="create.url()"><Button><Plus class="mr-2 h-4 w-4" />{{ t('purchaseOrders.createOrder') }}</Button></Link>
                </div>
            </template>
            <div class="p-4 md:p-6 pt-4 overflow-y-auto">
                <Table class="bg-transparent">
                    <TableHeader>
                        <TableRow class="border-b border-border hover:bg-muted/30">
                            <TableHead class="text-muted-foreground">{{ t('purchaseOrders.orderNumber') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('purchaseOrders.supplier') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('purchaseOrders.orderDate') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('common.status') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('purchaseOrders.total') }}</TableHead>
                            <TableHead class="w-[80px] text-muted-foreground">{{ t('common.actions') }}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="o in orders.data" :key="(o as any).id" class="border-b border-border hover:bg-muted/30">
                            <TableCell class="font-medium">{{ (o as any).order_number }}</TableCell>
                            <TableCell>{{ (o as any).supplier?.name ?? '-' }}</TableCell>
                            <TableCell>{{ (o as any).order_date }}</TableCell>
                            <TableCell><Badge>{{ (o as any).status }}</Badge></TableCell>
                            <TableCell>{{ (o as any).total }}</TableCell>
                            <TableCell>
                                <Link :href="`/warehouse/purchase-orders/${(o as any).id}`"><Button variant="ghost" size="icon"><Eye class="h-4 w-4" /></Button></Link>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <Pagination v-if="orders.links?.length" :links="orders.links" class="mt-4" />
            </div>
        </AppPageContent>
    </AppLayout>
</template>
