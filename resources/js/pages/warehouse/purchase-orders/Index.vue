<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Eye } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { usePermission } from '@/composables/usePermission';
import { index, create } from '@/actions/App/Http/Controllers/Warehouse/PurchaseOrderController';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
        <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <div><CardTitle>{{ t('purchaseOrders.title') }}</CardTitle><CardDescription>{{ t('common.view') }}</CardDescription></div>
                    <Link v-if="can('purchase_orders.create')" :href="create.url()"><Button><Plus class="mr-2 h-4 w-4" />{{ t('purchaseOrders.createOrder') }}</Button></Link>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{{ t('purchaseOrders.orderNumber') }}</TableHead>
                                <TableHead>{{ t('purchaseOrders.supplier') }}</TableHead>
                                <TableHead>{{ t('purchaseOrders.orderDate') }}</TableHead>
                                <TableHead>{{ t('common.status') }}</TableHead>
                                <TableHead>{{ t('purchaseOrders.total') }}</TableHead>
                                <TableHead class="w-[80px]">{{ t('common.actions') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="o in orders.data" :key="(o as any).id">
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
                    <div v-if="orders.links?.length" class="mt-4 flex gap-2">
                        <Link v-for="(link, i) in orders.links" :key="i" :href="link.url || '#'" class="rounded border px-3 py-1 text-sm" :class="{ 'opacity-50 pointer-events-none': !link.url }">{{ link.label }}</Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
