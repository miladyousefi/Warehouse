<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppPageContent from '@/components/AppPageContent.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { usePermission } from '@/composables/usePermission';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const { t } = useI18n();
const { can } = usePermission();
const locale = computed(() => (useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en'));

const props = defineProps<{
  order: Record<string, any>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: t('nav.restaurantOrders'), href: '/warehouse/restaurant-orders' },
  { title: props.order.order_code || 'Order' },
];

const canEditOrder = computed(() => can('restaurant_orders.edit') || can('restaurant_orders.monitor.confirm_cancel'));
const actionForm = useForm<{ status: string; payment_status: string; cancel_reason: string | null }>({
  status: props.order.status || 'pending',
  payment_status: props.order.payment_status || 'unpaid',
  cancel_reason: props.order.cancel_reason || null,
});

function updateOrder() {
  if (!canEditOrder.value) return;
  if (actionForm.status === 'cancelled' && !actionForm.cancel_reason) {
    actionForm.cancel_reason = 'other';
  }
  if (actionForm.status !== 'cancelled') {
    actionForm.cancel_reason = null;
  }

  actionForm.patch(`/warehouse/restaurant-orders/${props.order.id}/status`, {
    preserveScroll: true,
  });
}
</script>

<template>
  <Head :title="`Order ${order.order_code}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <AppPageContent>
      <template #header>
        <div class="p-4 pb-0 md:p-6 md:pb-0">
          <h1 class="text-xl font-semibold">Order {{ order.order_code }}</h1>
          <p class="text-sm text-muted-foreground">
            {{ t('restaurantMenu.tableLabel') }}: {{ order.table?.name || order.table?.table_number || '-' }}
          </p>
        </div>
      </template>

      <div class="space-y-4 p-4 pt-4 md:p-6 md:pt-4">
        <div class="flex flex-wrap gap-2">
          <Link href="/warehouse/restaurant-orders">
            <Button variant="outline" size="sm">Back</Button>
          </Link>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
          <Card>
            <CardHeader><CardTitle class="text-sm">Status</CardTitle></CardHeader>
            <CardContent class="text-lg font-semibold">{{ order.status }}</CardContent>
          </Card>
          <Card>
            <CardHeader><CardTitle class="text-sm">Payment</CardTitle></CardHeader>
            <CardContent class="text-lg font-semibold">{{ order.payment_status }}</CardContent>
          </Card>
          <Card>
            <CardHeader><CardTitle class="text-sm">{{ t('common.total') }}</CardTitle></CardHeader>
            <CardContent class="text-lg font-semibold">{{ Number(order.subtotal).toFixed(2) }}</CardContent>
          </Card>
          <Card>
            <CardHeader><CardTitle class="text-sm">Source</CardTitle></CardHeader>
            <CardContent class="text-lg font-semibold">{{ order.source || '-' }}</CardContent>
          </Card>
        </div>

        <Card v-if="canEditOrder">
          <CardHeader><CardTitle class="text-sm">Order Actions</CardTitle></CardHeader>
          <CardContent class="space-y-3">
            <div class="grid gap-3 md:grid-cols-3">
              <div>
                <label class="mb-1 block text-xs text-muted-foreground">Status</label>
                <select v-model="actionForm.status" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                  <option value="pending">pending</option>
                  <option value="confirmed">confirmed</option>
                  <option value="served">served</option>
                  <option value="closed">closed</option>
                  <option value="cancelled">cancelled</option>
                </select>
              </div>
              <div>
                <label class="mb-1 block text-xs text-muted-foreground">Payment</label>
                <select v-model="actionForm.payment_status" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                  <option value="unpaid">unpaid</option>
                  <option value="paid">paid</option>
                </select>
              </div>
              <div v-if="actionForm.status === 'cancelled'">
                <label class="mb-1 block text-xs text-muted-foreground">Cancel reason</label>
                <select v-model="actionForm.cancel_reason" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                  <option value="customer_request">customer request</option>
                  <option value="out_of_stock">out of stock</option>
                  <option value="kitchen_issue">kitchen issue</option>
                  <option value="no_response">no response</option>
                  <option value="other">other</option>
                </select>
              </div>
            </div>
            <div class="flex justify-end">
              <Button size="sm" :disabled="actionForm.processing" @click="updateOrder">Save Changes</Button>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Items</CardTitle>
          </CardHeader>
          <CardContent>
            <Table class="rounded-md border">
              <TableHeader>
                <TableRow>
                  <TableHead>{{ t('common.name') }}</TableHead>
                  <TableHead class="text-right">{{ t('common.quantity') }}</TableHead>
                  <TableHead class="text-right">Unit Price</TableHead>
                  <TableHead class="text-right">{{ t('common.total') }}</TableHead>
                  <TableHead>Note</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="item in order.items || []" :key="item.id">
                  <TableCell>{{ item.menu_item?.[locale] || item.menu_item?.name_tr || '-' }}</TableCell>
                  <TableCell class="text-right">{{ item.quantity }}</TableCell>
                  <TableCell class="text-right">{{ Number(item.unit_price).toFixed(2) }}</TableCell>
                  <TableCell class="text-right">{{ Number(item.total_price).toFixed(2) }}</TableCell>
                  <TableCell>{{ item.note || '-' }}</TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </CardContent>
        </Card>

        <Card v-if="order.cancel_reason">
          <CardHeader><CardTitle class="text-sm">Cancel Reason</CardTitle></CardHeader>
          <CardContent>{{ order.cancel_reason }}</CardContent>
        </Card>
      </div>
    </AppPageContent>
  </AppLayout>
</template>
