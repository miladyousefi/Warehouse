<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Check, CookingPot, RefreshCw, X } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { usePermission } from '@/composables/usePermission';

const { t, locale } = useI18n();
const { can } = usePermission();

const props = defineProps<{
    orders: Array<Record<string, any>>;
    confirmedOrders: Array<Record<string, any>>;
}>();

const localeField = computed(() => (locale.value === 'tr' ? 'name_tr' : 'name_en'));
const statusForm = useForm<{ status: string; payment_status: string; cancel_reason: string | null }>({
    status: 'pending',
    payment_status: 'unpaid',
    cancel_reason: null,
});
const canEditOrders = computed(() => can('restaurant_orders.edit') || can('restaurant_orders.monitor.confirm_cancel'));
const selectedOrder = ref<Record<string, any> | null>(null);
const cancelTargetOrder = ref<Record<string, any> | null>(null);
const cancelReason = ref('customer_request');

function refreshBoard(): void {
    router.reload({ only: ['orders', 'confirmedOrders'], preserveScroll: true });
}

function confirmOrder(order: Record<string, any>): void {
    if (!canEditOrders.value) return;

    statusForm.status = 'confirmed';
    statusForm.payment_status = order.payment_status;
    statusForm.cancel_reason = null;

    statusForm.patch(`/warehouse/restaurant-orders/${order.id}/status`, {
        preserveScroll: true,
        onSuccess: () => refreshBoard(),
    });
}

function openCancelModal(order: Record<string, any>): void {
    if (!canEditOrders.value) return;
    cancelTargetOrder.value = order;
    cancelReason.value = 'customer_request';
}

function closeCancelModal(): void {
    cancelTargetOrder.value = null;
}

function submitCancel(): void {
    if (!cancelTargetOrder.value || !canEditOrders.value) return;

    statusForm.status = 'cancelled';
    statusForm.payment_status = cancelTargetOrder.value.payment_status;
    statusForm.cancel_reason = cancelReason.value;

    statusForm.patch(`/warehouse/restaurant-orders/${cancelTargetOrder.value.id}/status`, {
        preserveScroll: true,
        onSuccess: () => {
            closeCancelModal();
            refreshBoard();
        },
    });
}

function setupBroadcastListeners(): void {
    const w = window as any;
    if (!w.Echo) return;

    w.Echo.private('restaurant-orders').listen('.order.placed', (payload: any) => {
        refreshBoard();
        notifyNewOrder(payload ?? {});
    });
    w.Echo.private('restaurant-orders').listen('.order.updated', () => refreshBoard());
}

function notifyNewOrder(order: { order_code?: string }): void {
    const text = order?.order_code
        ? `New order received. Order ${order.order_code}`
        : 'New order received';

    try {
        const audioContext = new (window.AudioContext || (window as any).webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gain = audioContext.createGain();
        oscillator.type = 'sine';
        oscillator.frequency.value = 880;
        gain.gain.value = 0.0001;
        oscillator.connect(gain);
        gain.connect(audioContext.destination);
        oscillator.start();
        gain.gain.exponentialRampToValueAtTime(0.2, audioContext.currentTime + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, audioContext.currentTime + 0.45);
        oscillator.stop(audioContext.currentTime + 0.5);
    } catch {
        // no-op
    }

    try {
        if ('speechSynthesis' in window) {
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'en-US';
            utterance.rate = 1;
            window.speechSynthesis.speak(utterance);
        }
    } catch {
        // no-op
    }
}

function openOrderModal(order: Record<string, any>): void {
    selectedOrder.value = order;
}

function closeOrderModal(): void {
    selectedOrder.value = null;
}

function cleanupBroadcastListeners(): void {
    const w = window as any;
    if (!w.Echo) return;
    w.Echo.leave('private-restaurant-orders');
}

onMounted(() => setupBroadcastListeners());
onUnmounted(() => cleanupBroadcastListeners());
</script>

<template>
    <Head title="Kitchen Monitor" />
    <div class="min-h-screen bg-background text-foreground">
        <div class="mx-auto max-w-7xl p-4 md:p-6">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h1 class="text-2xl font-semibold">Kitchen Monitor</h1>
                    <p class="text-sm text-muted-foreground">Only confirm or cancel incoming orders.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link href="/warehouse/restaurant-orders">
                        <Button size="sm" variant="outline">{{ t('nav.restaurantOrders') }}</Button>
                    </Link>
                    <Button size="sm" variant="outline" @click="refreshBoard">
                        <RefreshCw class="mr-2 h-4 w-4" />
                        Refresh
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <CookingPot class="h-4 w-4 text-rose-500" />
                        Open Orders
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="orders.length === 0" class="text-sm text-muted-foreground">No open orders.</div>
                    <div v-else class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        <article v-for="order in orders" :key="order.id" class="rounded-md border p-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-semibold">{{ order.order_code }}</p>
                                    <p class="text-xs text-muted-foreground">{{ order.table?.name || order.table?.table_number || '-' }}</p>
                                </div>
                                <span class="rounded-full border px-2 py-0.5 text-[10px]">{{ order.status }}</span>
                            </div>

                            <p class="mt-2 text-xs text-muted-foreground">{{ order.items?.length || 0 }} items • {{ Number(order.subtotal).toFixed(2) }}</p>

                            <div class="mt-3 flex items-center gap-2">
                                <Button size="sm" variant="outline" class="flex-1" @click="openOrderModal(order)">Details</Button>
                                <Button
                                    size="sm"
                                    class="flex-1"
                                    :disabled="!canEditOrders || statusForm.processing"
                                    @click="confirmOrder(order)"
                                >
                                    <Check class="mr-1 h-4 w-4" />
                                    Confirm
                                </Button>
                                <Button
                                    size="sm"
                                    variant="destructive"
                                    class="flex-1"
                                    :disabled="!canEditOrders || statusForm.processing"
                                    @click="openCancelModal(order)"
                                >
                                    <X class="mr-1 h-4 w-4" />
                                    Cancel
                                </Button>
                            </div>
                        </article>
                    </div>
                </CardContent>
            </Card>

            <Card class="mt-4">
                <CardHeader>
                    <CardTitle>Confirmed Orders</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="confirmedOrders.length === 0" class="text-sm text-muted-foreground">No confirmed orders.</div>
                    <div v-else class="space-y-2">
                        <article
                            v-for="order in confirmedOrders"
                            :key="order.id"
                            class="flex items-center justify-between gap-3 rounded-md border p-3"
                        >
                            <div>
                                <p class="text-sm font-semibold">{{ order.order_code }}</p>
                                <p class="text-xs text-muted-foreground">{{ order.table?.name || order.table?.table_number || '-' }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ order.updated_at ? new Date(order.updated_at).toLocaleString() : '-' }}
                                </p>
                            </div>
                            <Button size="sm" variant="outline" @click="openOrderModal(order)">Open Order</Button>
                        </article>
                    </div>
                </CardContent>
            </Card>

            <div v-if="selectedOrder" class="fixed inset-0 z-[120] flex items-center justify-center bg-black/35 p-4">
                <div class="w-full max-w-4xl rounded-lg border bg-background p-4 shadow-xl">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold">Order {{ selectedOrder.order_code }}</h3>
                            <p class="text-sm text-muted-foreground">
                                {{ t('restaurantMenu.tableLabel') }}: {{ selectedOrder.table?.name || selectedOrder.table?.table_number || '-' }}
                            </p>
                        </div>
                        <Button size="sm" variant="outline" @click="closeOrderModal">Close</Button>
                    </div>

                    <div class="grid gap-4 md:grid-cols-4">
                        <Card>
                            <CardHeader><CardTitle class="text-sm">Status</CardTitle></CardHeader>
                            <CardContent class="text-lg font-semibold">{{ selectedOrder.status }}</CardContent>
                        </Card>
                        <Card>
                            <CardHeader><CardTitle class="text-sm">Payment</CardTitle></CardHeader>
                            <CardContent class="text-lg font-semibold">{{ selectedOrder.payment_status }}</CardContent>
                        </Card>
                        <Card>
                            <CardHeader><CardTitle class="text-sm">{{ t('common.total') }}</CardTitle></CardHeader>
                            <CardContent class="text-lg font-semibold">{{ Number(selectedOrder.subtotal).toFixed(2) }}</CardContent>
                        </Card>
                        <Card>
                            <CardHeader><CardTitle class="text-sm">Source</CardTitle></CardHeader>
                            <CardContent class="text-lg font-semibold">{{ selectedOrder.source || '-' }}</CardContent>
                        </Card>
                    </div>

                    <Card class="mt-4">
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
                                    <TableRow v-for="item in selectedOrder.items || []" :key="item.id">
                                        <TableCell>{{ item.menu_item?.[localeField] || item.menu_item?.name_tr || '-' }}</TableCell>
                                        <TableCell class="text-right">{{ item.quantity }}</TableCell>
                                        <TableCell class="text-right">{{ Number(item.unit_price || 0).toFixed(2) }}</TableCell>
                                        <TableCell class="text-right">{{ Number(item.total_price || 0).toFixed(2) }}</TableCell>
                                        <TableCell>{{ item.note || '-' }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <div v-if="selectedOrder.customer_note" class="mt-3 rounded-md border p-3 text-sm">
                        <p class="mb-1 text-xs text-muted-foreground">Customer note</p>
                        <p>{{ selectedOrder.customer_note }}</p>
                    </div>
                    <div v-if="selectedOrder.cancel_reason" class="mt-3 rounded-md border p-3 text-sm">
                        <p class="mb-1 text-xs text-muted-foreground">Cancel reason</p>
                        <p>{{ selectedOrder.cancel_reason }}</p>
                    </div>
                </div>
            </div>

            <div v-if="cancelTargetOrder" class="fixed inset-0 z-[130] flex items-center justify-center bg-black/35 p-4">
                <div class="w-full max-w-md rounded-lg border bg-background p-4 shadow-xl">
                    <h3 class="text-base font-semibold">Cancel Order</h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ cancelTargetOrder.order_code }} - {{ cancelTargetOrder.table?.name || cancelTargetOrder.table?.table_number || '-' }}
                    </p>

                    <div class="mt-3">
                        <label class="mb-1 block text-sm font-medium">Reason</label>
                        <select v-model="cancelReason" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="customer_request">customer request</option>
                            <option value="out_of_stock">out of stock</option>
                            <option value="kitchen_issue">kitchen issue</option>
                            <option value="no_response">no response</option>
                            <option value="other">other</option>
                        </select>
                    </div>

                    <div class="mt-4 flex items-center justify-end gap-2">
                        <Button size="sm" variant="outline" @click="closeCancelModal">Close</Button>
                        <Button size="sm" variant="destructive" :disabled="statusForm.processing" @click="submitCancel">Submit Cancel</Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
