<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { BellRing, ClipboardList, CreditCard, PlusCircle, QrCode, UtensilsCrossed } from 'lucide-vue-next';
import { usePermission } from '@/composables/usePermission';

const { t } = useI18n();
const { can } = usePermission();

const props = defineProps<{
  orders: { data: Array<Record<string, any>> };
  calls: Array<Record<string, any>>;
  stats: { pending_orders: number; unpaid_orders: number; pending_calls: number };
  tables: Array<Record<string, any>>;
  filters: { status: string | null };
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: t('nav.restaurantOrders'), href: '/warehouse/restaurant-orders' },
];
const manualOrderCreateUrl = '/warehouse/restaurant-orders/manual/create';

const status = ref(props.filters.status || '');
const statusForm = useForm({ status: 'pending', payment_status: 'unpaid' });
const tableCreateForm = useForm({
  table_number: '',
  name: '',
  capacity: 4,
  section: 'indoor',
  is_active: true,
});
const tableEditForm = useForm({
  table_number: '',
  name: '',
  capacity: 4,
  section: 'indoor',
  is_active: true,
});
const editingTableId = ref<number | null>(null);

const topStats = computed(() => [
  { key: 'pending', icon: ClipboardList, label: t('restaurantMenu.orderStatus'), value: props.stats.pending_orders },
  { key: 'unpaid', icon: CreditCard, label: t('restaurantMenu.paymentStatus'), value: props.stats.unpaid_orders },
  { key: 'calls', icon: BellRing, label: t('restaurantMenu.pendingCalls'), value: props.stats.pending_calls },
]);

function filterOrders() {
  router.get('/warehouse/restaurant-orders', { status: status.value || undefined }, { preserveState: true });
}

function updateOrder(order: Record<string, any>) {
  statusForm.status = order.status;
  statusForm.payment_status = order.payment_status;
  statusForm.patch(route('warehouse.restaurant-orders.update-status', { order: order.id }));
}

function markCallHandled(callId: number) {
  router.patch(route('warehouse.restaurant-orders.calls.handled', { call: callId }));
}

function createTable() {
  tableCreateForm.post(route('warehouse.restaurant-orders.tables.store'), {
    preserveScroll: true,
    onSuccess: () => {
      tableCreateForm.reset();
      tableCreateForm.capacity = 4;
      tableCreateForm.section = 'indoor';
      tableCreateForm.is_active = true;
      refreshBoard();
    },
  });
}

function startEditTable(table: Record<string, any>) {
  editingTableId.value = table.id;
  tableEditForm.table_number = String(table.table_number || '');
  tableEditForm.name = String(table.name || '');
  tableEditForm.capacity = Number(table.capacity || 1);
  tableEditForm.section = String(table.section || '');
  tableEditForm.is_active = Boolean(table.is_active);
}

function cancelEditTable() {
  editingTableId.value = null;
  tableEditForm.reset();
}

function saveTable(tableId: number) {
  tableEditForm.patch(route('warehouse.restaurant-orders.tables.update', { table: tableId }), {
    preserveScroll: true,
    onSuccess: () => {
      editingTableId.value = null;
      refreshBoard();
    },
  });
}

function regenerateTableLink(tableId: number) {
  router.patch(
    route('warehouse.restaurant-orders.tables.regenerate-link', { table: tableId }),
    {},
    { preserveScroll: true, onSuccess: () => refreshBoard() }
  );
}

async function copyTableUrl(url: string | null) {
  if (!url) return;
  await navigator.clipboard.writeText(url);
}

function refreshBoard() {
  router.reload({ only: ['orders', 'calls', 'stats', 'tables'], preserveScroll: true });
}

function setupBroadcastListeners() {
  const w = window as any;
  if (!w.Echo) return;

  w.Echo.private('restaurant-calls').listen('.waiter.called', () => {
    refreshBoard();
  });

  w.Echo.private('restaurant-orders').listen('.order.placed', () => {
    refreshBoard();
  });
}

function cleanupBroadcastListeners() {
  const w = window as any;
  if (!w.Echo) return;

  w.Echo.leave('private-restaurant-calls');
  w.Echo.leave('private-restaurant-orders');
}

onMounted(() => {
  setupBroadcastListeners();
});

onUnmounted(() => {
  cleanupBroadcastListeners();
});
</script>

<template>
  <Head :title="t('nav.restaurantOrders')" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
      <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-2 inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
          <UtensilsCrossed class="h-3.5 w-3.5" /> {{ t('restaurantMenu.publicMenu') }}
        </div>
        <h1 class="text-2xl font-black tracking-wide">{{ t('nav.restaurantOrders') }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ t('restaurantMenu.liveBoardInfo') }}</p>
        <div class="mt-3 flex flex-wrap gap-2">
          <Link v-if="can('restaurant_orders.take_order')" :href="manualOrderCreateUrl">
            <Button>
              <PlusCircle class="mr-2 h-4 w-4" />
              {{ t('restaurantMenu.manualOrder') }}
            </Button>
          </Link>
        </div>
      </section>

      <section class="grid gap-4 sm:grid-cols-3">
        <Card v-for="stat in topStats" :key="stat.key" class="border-slate-200 bg-white shadow-sm">
          <CardContent class="flex items-center justify-between p-4">
            <div>
              <div class="text-xs text-slate-500">{{ stat.label }}</div>
              <div class="text-2xl font-bold text-slate-900">{{ stat.value }}</div>
            </div>
            <component :is="stat.icon" class="h-5 w-5 text-slate-700" />
          </CardContent>
        </Card>
      </section>

      <Card>
        <CardHeader><CardTitle class="flex items-center gap-2"><BellRing class="h-4 w-4 text-amber-500" />{{ t('restaurantMenu.pendingCalls') }}</CardTitle></CardHeader>
        <CardContent>
          <div v-if="calls.length === 0" class="text-sm text-muted-foreground">{{ t('restaurantMenu.noPendingCalls') }}</div>
          <div v-else class="grid gap-3 md:grid-cols-2">
            <div
              v-for="call in calls"
              :key="call.id"
              class="rounded-xl border border-amber-300/40 bg-amber-50/60 p-3"
            >
              <div class="flex items-center justify-between">
                <div>
                  <div class="font-semibold">{{ call.table?.name || call.table?.table_number }}</div>
                  <div class="text-xs text-muted-foreground">{{ call.note || '-' }}</div>
                </div>
                <Button size="sm" @click="markCallHandled(call.id)">{{ t('restaurantMenu.markHandled') }}</Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle class="flex items-center gap-2"><QrCode class="h-4 w-4 text-cyan-500" />{{ t('restaurantMenu.tableQrLinks') }}</CardTitle></CardHeader>
        <CardContent class="space-y-4">
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <div class="mb-3 text-sm font-semibold text-slate-700">{{ t('restaurantMenu.addTable') }}</div>
            <div class="grid gap-2 md:grid-cols-6">
              <Input v-model="tableCreateForm.table_number" :placeholder="t('restaurantMenu.tableNumber')" class="md:col-span-1" />
              <Input v-model="tableCreateForm.name" :placeholder="t('common.name')" class="md:col-span-2" />
              <Input v-model="tableCreateForm.capacity" type="number" min="1" :placeholder="t('restaurantMenu.capacity')" class="md:col-span-1" />
              <Input v-model="tableCreateForm.section" :placeholder="t('restaurantMenu.section')" class="md:col-span-1" />
              <Button class="md:col-span-1" :disabled="tableCreateForm.processing" @click="createTable">{{ t('common.add') }}</Button>
            </div>
            <label class="mt-2 inline-flex items-center gap-2 text-xs text-slate-600">
              <input v-model="tableCreateForm.is_active" type="checkbox" />
              {{ t('restaurantMenu.active') }}
            </label>
          </div>

          <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="table in tables" :key="table.id" class="rounded-xl border bg-card p-3">
              <template v-if="editingTableId === table.id">
                <div class="space-y-2">
                  <Input v-model="tableEditForm.table_number" :placeholder="t('restaurantMenu.tableNumber')" />
                  <Input v-model="tableEditForm.name" :placeholder="t('common.name')" />
                  <Input v-model="tableEditForm.capacity" type="number" min="1" :placeholder="t('restaurantMenu.capacity')" />
                  <Input v-model="tableEditForm.section" :placeholder="t('restaurantMenu.section')" />
                  <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                    <input v-model="tableEditForm.is_active" type="checkbox" />
                    {{ t('restaurantMenu.active') }}
                  </label>
                  <div class="flex gap-2">
                    <Button size="sm" class="flex-1" :disabled="tableEditForm.processing" @click="saveTable(table.id)">{{ t('common.save') }}</Button>
                    <Button size="sm" variant="outline" class="flex-1" @click="cancelEditTable">{{ t('common.cancel') }}</Button>
                  </div>
                </div>
              </template>
              <template v-else>
                <div class="mb-2 flex items-center justify-between gap-2">
                  <div>
                    <div class="font-medium">{{ table.name || table.table_number }}</div>
                    <div class="text-xs text-slate-500">{{ t('restaurantMenu.tableNumber') }}: {{ table.table_number }}</div>
                    <div class="text-xs text-slate-500">{{ t('restaurantMenu.capacity') }}: {{ table.capacity || '-' }}</div>
                  </div>
                  <span class="rounded-full border px-2 py-0.5 text-[10px]" :class="table.is_active ? 'border-emerald-300 text-emerald-700' : 'border-slate-300 text-slate-500'">
                    {{ table.is_active ? t('restaurantMenu.active') : t('restaurantMenu.inactive') }}
                  </span>
                </div>
                <img
                  v-if="table.order_url"
                  :src="`https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(table.order_url)}`"
                  alt="qr"
                  class="mx-auto h-36 w-36 rounded-lg bg-white p-1"
                />
                <div class="mt-2 break-all text-[11px] text-muted-foreground">{{ table.order_url || '-' }}</div>
                <div class="mt-2 grid grid-cols-2 gap-2">
                  <Button size="sm" variant="outline" @click="copyTableUrl(table.order_url)">{{ t('restaurantMenu.copyLink') }}</Button>
                  <Button size="sm" variant="outline" @click="startEditTable(table)">{{ t('common.edit') }}</Button>
                  <Button size="sm" class="col-span-2" @click="regenerateTableLink(table.id)">{{ t('restaurantMenu.regenerateLink') }}</Button>
                </div>
              </template>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <Input v-model="status" :placeholder="t('restaurantMenu.orderStatus')" class="sm:max-w-xs" />
            <Button variant="outline" @click="filterOrders">{{ t('common.search') }}</Button>
          </div>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>{{ t('restaurantMenu.orderCode') }}</TableHead>
                <TableHead>{{ t('restaurantMenu.tableLabel') }}</TableHead>
                <TableHead>{{ t('restaurantMenu.orderStatus') }}</TableHead>
                <TableHead>{{ t('restaurantMenu.paymentStatus') }}</TableHead>
                <TableHead class="text-right">{{ t('common.total') }}</TableHead>
                <TableHead class="text-right">{{ t('common.actions') }}</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="order in orders.data" :key="order.id">
                <TableCell>
                  <div class="font-medium">{{ order.order_code }}</div>
                  <div class="text-xs text-muted-foreground">{{ order.items?.length || 0 }} items</div>
                </TableCell>
                <TableCell>{{ order.table?.name || order.table?.table_number || '-' }}</TableCell>
                <TableCell>
                  <select v-model="order.status" class="rounded-md border border-input bg-background px-2 py-1 text-sm">
                    <option value="pending">pending</option>
                    <option value="confirmed">confirmed</option>
                    <option value="served">served</option>
                    <option value="closed">closed</option>
                    <option value="cancelled">cancelled</option>
                  </select>
                </TableCell>
                <TableCell>
                  <select v-model="order.payment_status" class="rounded-md border border-input bg-background px-2 py-1 text-sm">
                    <option value="unpaid">unpaid</option>
                    <option value="paid">paid</option>
                  </select>
                </TableCell>
                <TableCell class="text-right">{{ Number(order.subtotal).toFixed(2) }}</TableCell>
                <TableCell class="text-right">
                  <Button size="sm" @click="updateOrder(order)">{{ t('common.save') }}</Button>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
