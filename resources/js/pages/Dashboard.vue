<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Chart as ChartJS, ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title } from 'chart.js';
import {
    ArrowRightLeft,
    BellRing,
    Check,
    DollarSign,
    Package,
    TrendingDown,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted } from 'vue';
import { Pie, Bar } from 'vue-chartjs';
import { useI18n } from 'vue-i18n';
import { index as stockMovementsIndex } from '@/actions/App/Http/Controllers/Warehouse/StockMovementController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { usePermission } from '@/composables/usePermission';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title);

interface Props {
    lowStockCount: number;
    totalProducts: number;
    totalValue: number;
    recentMovements: Array<{
        id: number;
        type: string;
        quantity: string;
        movement_date: string;
        product?: { name_tr: string; name_en: string };
        warehouse?: { name_tr: string; name_en: string };
    }>;
    movementsByType?: Record<string, number>;
    restaurantBoard?: {
        can_view_calls: boolean;
        can_view_tables: boolean;
        can_handle_calls: boolean;
        pending_calls: Array<Record<string, any>>;
        tables: Array<Record<string, any>>;
    };
}

const props = defineProps<Props>();
const { t, locale } = useI18n();
const { can } = usePermission();

const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.dashboard'), href: dashboard().url }];

const canViewCallsSection = computed(() => {
    const fromServer = props.restaurantBoard?.can_view_calls ?? false;
    return fromServer || can('restaurant_orders.view') || can('restaurant_orders.edit') || can('restaurant_orders.calls.handle');
});

const canViewTablesSection = computed(() => {
    const fromServer = props.restaurantBoard?.can_view_tables ?? false;
    return fromServer || can('restaurant_orders.view') || can('restaurant_orders.edit');
});

const canHandleCalls = computed(() => {
    const fromServer = props.restaurantBoard?.can_handle_calls ?? false;
    return fromServer || can('restaurant_orders.edit') || can('restaurant_orders.calls.handle');
});

const restaurantPendingCalls = computed(() => props.restaurantBoard?.pending_calls || []);
const restaurantTables = computed(() => props.restaurantBoard?.tables || []);

const chartDataMovementType = computed(() => {
    const data = props.movementsByType || {};
    const labels = Object.keys(data).map((type) => {
        const typeMap: Record<string, string> = {
            in: t('nav.input'),
            out: t('nav.output'),
            transfer: t('nav.transfer'),
            adjustment: t('nav.adjustment'),
        };
        return typeMap[type] || type;
    });

    return {
        labels,
        datasets: [
            {
                label: t('stockMovements.title'),
                data: Object.values(data),
                backgroundColor: ['#10b981', '#ef4444', '#3b82f6', '#f59e0b'],
                borderColor: ['#059669', '#dc2626', '#1d4ed8', '#d97706'],
                borderWidth: 2,
            },
        ],
    };
});

const chartDataWarehouse = computed(() => {
    const warehouseData: Record<string, number> = {};

    props.recentMovements?.forEach((m) => {
        const name = (m.warehouse as any)?.[locale.value === 'tr' ? 'name_tr' : 'name_en'] || 'Unknown';
        warehouseData[name] = (warehouseData[name] || 0) + 1;
    });

    return {
        labels: Object.keys(warehouseData),
        datasets: [
            {
                label: t('stockMovements.title'),
                data: Object.values(warehouseData),
                backgroundColor: '#3b82f6',
                borderColor: '#1d4ed8',
                borderWidth: 1,
            },
        ],
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: true,
};

function refreshDashboard(): void {
    router.reload({
        only: ['lowStockCount', 'totalProducts', 'totalValue', 'recentMovements', 'movementsByType', 'restaurantBoard'],
        preserveScroll: true,
    });
}

function markCallHandled(callId: number): void {
    if (!canHandleCalls.value) return;

    router.patch(`/warehouse/restaurant-orders/calls/${callId}/handled`, {}, {
        preserveScroll: true,
        onSuccess: () => refreshDashboard(),
    });
}

function setupBroadcastListeners(): void {
    const w = window as any;
    if (!w.Echo) return;

    w.Echo.private('restaurant-calls').listen('.waiter.called', () => refreshDashboard());
    w.Echo.private('restaurant-orders').listen('.order.placed', () => refreshDashboard());
    w.Echo.private('restaurant-orders').listen('.order.updated', () => refreshDashboard());
}

function cleanupBroadcastListeners(): void {
    const w = window as any;
    if (!w.Echo) return;
    w.Echo.leave('private-restaurant-calls');
    w.Echo.leave('private-restaurant-orders');
}

onMounted(() => setupBroadcastListeners());
onUnmounted(() => cleanupBroadcastListeners());
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <div class="grid gap-4 md:grid-cols-4">
                <Card class="border-l-4 border-l-emerald-500">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-3">
                        <div>
                            <CardTitle class="text-sm font-medium text-muted-foreground">{{ t('dashboard.totalProducts') }}</CardTitle>
                            <div class="mt-2 text-2xl font-bold">{{ totalProducts }}</div>
                        </div>
                        <Package class="h-8 w-8 text-emerald-500 opacity-80" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">{{ t('stock.product') }}</p>
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-rose-500">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-3">
                        <div>
                            <CardTitle class="text-sm font-medium text-muted-foreground">{{ t('dashboard.lowStock') }}</CardTitle>
                            <div class="mt-2 text-2xl font-bold">{{ lowStockCount }}</div>
                        </div>
                        <TrendingDown class="h-8 w-8 text-rose-500 opacity-80" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">{{ t('stock.lowStock') }}</p>
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-blue-500">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-3">
                        <div>
                            <CardTitle class="text-sm font-medium text-muted-foreground">{{ t('dashboard.totalValue') }}</CardTitle>
                            <div class="mt-2 text-2xl font-bold">${{ (Number(totalValue) / 1000).toFixed(1) }}K</div>
                        </div>
                        <DollarSign class="h-8 w-8 text-blue-500 opacity-80" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">Inventory value</p>
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-amber-500">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-3">
                        <div>
                            <CardTitle class="text-sm font-medium text-muted-foreground">{{ t('stockMovements.title') }}</CardTitle>
                            <div class="mt-2 text-2xl font-bold">{{ recentMovements?.length || 0 }}</div>
                        </div>
                        <ArrowRightLeft class="h-8 w-8 text-amber-500 opacity-80" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">Recent activity</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <Card class="shadow-sm">
                    <CardHeader>
                        <CardTitle class="text-base">{{ t('dashboard.movementsByType') }}</CardTitle>
                        <CardDescription>Distribution of stock movements</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="chartDataMovementType.datasets[0].data.length > 0" style="height: 280px">
                            <Pie :data="chartDataMovementType" :options="chartOptions" />
                        </div>
                        <p v-else class="py-8 text-center text-sm text-muted-foreground">{{ t('dashboard.noMovements') }}</p>
                    </CardContent>
                </Card>

                <Card class="shadow-sm">
                    <CardHeader>
                        <CardTitle class="text-base">{{ t('dashboard.movementsByWarehouse') }}</CardTitle>
                        <CardDescription>Activity per warehouse location</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="chartDataWarehouse.datasets[0].data.length > 0" style="height: 280px">
                            <Bar :data="chartDataWarehouse" :options="chartOptions" />
                        </div>
                        <p v-else class="py-8 text-center text-sm text-muted-foreground">{{ t('dashboard.noMovements') }}</p>
                    </CardContent>
                </Card>
            </div>

            <div v-if="canViewCallsSection || canViewTablesSection" class="grid gap-4 xl:grid-cols-[340px_1fr]">
                <Card v-if="canViewCallsSection" class="shadow-sm">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <BellRing class="h-4 w-4 text-amber-500" />
                            Pending Waiter Calls
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="restaurantPendingCalls.length === 0" class="text-sm text-muted-foreground">No pending waiter calls.</div>
                        <div v-else class="space-y-2">
                            <article v-for="call in restaurantPendingCalls" :key="call.id" class="rounded-md border bg-muted/30 p-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="font-semibold">{{ call.table?.name || call.table?.table_number || '-' }}</p>
                                        <p class="mt-1 text-xs text-muted-foreground">{{ call.note || '-' }}</p>
                                    </div>
                                    <Button
                                        v-if="canHandleCalls"
                                        size="icon-sm"
                                        :title="t('restaurantMenu.markHandled')"
                                        @click="markCallHandled(call.id)"
                                    >
                                        <Check class="h-4 w-4" />
                                        <span class="sr-only">{{ t('restaurantMenu.markHandled') }}</span>
                                    </Button>
                                </div>
                            </article>
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="canViewTablesSection" class="shadow-sm">
                    <CardHeader>
                        <CardTitle class="text-base">Table Orders</CardTitle>
                        <CardDescription>Table cards with latest order and recent logs.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-3 md:grid-cols-2">
                            <article v-for="table in restaurantTables" :key="table.id" class="rounded-md border p-3">
                                <div class="mb-3 rounded-md border bg-muted/20 p-3 text-sm">
                                    <div class="font-semibold">{{ table.name || table.table_number }}</div>
                                    <div class="text-xs text-muted-foreground">Table: {{ table.table_number }}</div>
                                    <div class="text-xs text-muted-foreground">Last order: {{ table.last_order?.order_code || '-' }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        Payment: {{ table.last_order?.payment_status || '-' }}
                                    </div>
                                </div>

                                <div v-if="(table.order_log || []).length === 0" class="text-sm text-muted-foreground">
                                    No orders for this table.
                                </div>
                                <div v-else class="space-y-2">
                                    <article v-for="order in table.order_log" :key="order.id" class="rounded-md border p-2">
                                        <div class="flex items-center justify-between gap-2">
                                            <div>
                                                <p class="text-sm font-semibold">{{ order.order_code }}</p>
                                                <p class="text-xs text-muted-foreground">
                                                    {{ order.status }} / {{ order.payment_status }} / {{ Number(order.subtotal).toFixed(2) }}
                                                </p>
                                            </div>
                                            <Link :href="`/warehouse/restaurant-orders/${order.id}`">
                                                <Button size="sm" variant="outline">View</Button>
                                            </Link>
                                        </div>
                                    </article>
                                </div>
                            </article>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card class="shadow-sm">
                <CardHeader class="border-b">
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-base">{{ t('dashboard.recentMovements') }}</CardTitle>
                            <CardDescription>Latest stock movements</CardDescription>
                        </div>
                        <Link :href="stockMovementsIndex.url()">
                            <Button variant="outline" size="sm">{{ t('common.viewAll') }}</Button>
                        </Link>
                    </div>
                </CardHeader>
                <CardContent class="pt-6">
                    <div v-if="recentMovements?.length" class="space-y-3">
                        <div
                            v-for="m in recentMovements.slice(0, 5)"
                            :key="m.id"
                            class="flex items-center justify-between rounded-lg border border-border p-3 transition-colors hover:bg-muted/30"
                        >
                            <div class="flex flex-1 items-center gap-3">
                                <div :class="`rounded-lg p-2 ${
                                    m.type === 'in' ? 'bg-emerald-100 dark:bg-emerald-900/30' :
                                    m.type === 'out' ? 'bg-rose-100 dark:bg-rose-900/30' :
                                    'bg-blue-100 dark:bg-blue-900/30'
                                }`">
                                    <ArrowRightLeft :class="`h-4 w-4 ${
                                        m.type === 'in' ? 'text-emerald-600 dark:text-emerald-400' :
                                        m.type === 'out' ? 'text-rose-600 dark:text-rose-400' :
                                        'text-blue-600 dark:text-blue-400'
                                    }`" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium">{{ (m.product as any)?.[locale === 'tr' ? 'name_tr' : 'name_en'] || '-' }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ (m.warehouse as any)?.[locale === 'tr' ? 'name_tr' : 'name_en'] || '-' }} ·
                                        <span :class="{
                                            'text-emerald-600 dark:text-emerald-400': m.type === 'in',
                                            'text-rose-600 dark:text-rose-400': m.type === 'out',
                                            'text-blue-600 dark:text-blue-400': m.type === 'transfer',
                                        }">
                                            {{ m.type === 'in' ? t('nav.input') : m.type === 'out' ? t('nav.output') : t('nav.transfer') }}
                                        </span>
                                        · {{ m.quantity }}
                                    </p>
                                </div>
                            </div>
                            <Badge variant="secondary" class="whitespace-nowrap">{{ m.movement_date }}</Badge>
                        </div>
                    </div>
                    <p v-else class="py-8 text-center text-sm text-muted-foreground">{{ t('dashboard.noMovements') }}</p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
