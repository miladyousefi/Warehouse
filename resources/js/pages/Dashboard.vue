<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRightLeft, BellRing, Check, Database, DollarSign, Package } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { index as stockMovementsIndex } from '@/actions/App/Http/Controllers/Warehouse/StockMovementController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { usePermission } from '@/composables/usePermission';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

interface Props {
    totalProducts: number;
    totalValue: number;
    totalMovementsCount: number;
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

const movementTypeRows = computed(() => {
    const src = props.movementsByType || {};
    const labels: Record<string, string> = {
        in: t('nav.input'),
        out: t('nav.output'),
        transfer: t('nav.transfer'),
        adjustment: t('nav.adjustment'),
    };

    const rows = Object.entries(src).map(([key, value]) => ({
        key,
        label: labels[key] || key,
        value: Number(value) || 0,
    }));

    const total = rows.reduce((sum, row) => sum + row.value, 0);

    return rows.map((row) => ({
        ...row,
        pct: total > 0 ? Math.round((row.value / total) * 100) : 0,
    }));
});

function formatMovementDate(dateValue: string): string {
    const date = new Date(dateValue);
    if (Number.isNaN(date.getTime())) return dateValue;

    return date.toLocaleString(locale.value === 'tr' ? 'tr-TR' : 'en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
}

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
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 p-4 md:p-6">
            <div class="flex justify-end">
                <a href="/warehouse/dashboard/backup-sql">
                    <Button variant="outline" size="sm">
                        <Database class="mr-2 h-4 w-4" />
                        {{ t('dashboard.backupDatabase') }}
                    </Button>
                </a>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <Card class="border-slate-200 shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm text-muted-foreground">{{ t('dashboard.totalProducts') }}</CardTitle>
                        <Package class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-semibold">{{ totalProducts }}</p>
                    </CardContent>
                </Card>

                <Card class="border-slate-200 shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm text-muted-foreground">{{ t('dashboard.totalMovements') }}</CardTitle>
                        <ArrowRightLeft class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-semibold">{{ totalMovementsCount }}</p>
                    </CardContent>
                </Card>

                <Card class="border-slate-200 shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm text-muted-foreground">{{ t('dashboard.totalValue') }}</CardTitle>
                        <DollarSign class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-semibold">{{ Number(totalValue).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                    </CardContent>
                </Card>

                <Card class="border-slate-200 shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm text-muted-foreground">{{ t('stockMovements.title') }}</CardTitle>
                        <ArrowRightLeft class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-semibold">{{ recentMovements?.length || 0 }}</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 xl:grid-cols-[380px_1fr]">
                <Card class="border-slate-200 shadow-sm">
                    <CardHeader>
                        <CardTitle class="text-base">{{ t('dashboard.movementsByType') }}</CardTitle>
                        <CardDescription>{{ locale === 'tr' ? 'Dağılım özeti' : 'Distribution summary' }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="movementTypeRows.length" class="space-y-3">
                            <div v-for="row in movementTypeRows" :key="row.key" class="space-y-1">
                                <div class="flex items-center justify-between text-sm">
                                    <span>{{ row.label }}</span>
                                    <span class="text-muted-foreground">{{ row.value }} ({{ row.pct }}%)</span>
                                </div>
                                <div class="h-2 rounded-full bg-slate-100">
                                    <div class="h-2 rounded-full bg-slate-500" :style="{ width: `${row.pct}%` }" />
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">{{ t('dashboard.noMovements') }}</p>
                    </CardContent>
                </Card>

                <Card class="border-slate-200 shadow-sm">
                    <CardHeader class="border-b">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <CardTitle class="text-base">{{ t('dashboard.recentMovements') }}</CardTitle>
                                <CardDescription>{{ locale === 'tr' ? 'Son hareket listesi' : 'Latest movement list' }}</CardDescription>
                            </div>
                            <Link :href="stockMovementsIndex.url()">
                                <Button variant="outline" size="sm">{{ t('common.viewAll') }}</Button>
                            </Link>
                        </div>
                    </CardHeader>
                    <CardContent class="pt-4">
                        <div v-if="recentMovements?.length" class="space-y-2">
                            <article
                                v-for="m in recentMovements.slice(0, 8)"
                                :key="m.id"
                                class="flex items-center justify-between gap-3 rounded-md border border-slate-200 bg-white p-3"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium">{{ (m.product as any)?.[locale === 'tr' ? 'name_tr' : 'name_en'] || '-' }}</p>
                                    <p class="truncate text-xs text-muted-foreground">
                                        {{ (m.warehouse as any)?.[locale === 'tr' ? 'name_tr' : 'name_en'] || '-' }}
                                        · {{ m.quantity }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge variant="outline" class="whitespace-nowrap">
                                        {{ m.type === 'in' ? t('nav.input') : m.type === 'out' ? t('nav.output') : m.type === 'adjustment' ? t('nav.adjustment') : t('nav.transfer') }}
                                    </Badge>
                                    <Badge variant="secondary" class="whitespace-nowrap">{{ formatMovementDate(m.movement_date) }}</Badge>
                                </div>
                            </article>
                        </div>
                        <p v-else class="py-4 text-sm text-muted-foreground">{{ t('dashboard.noMovements') }}</p>
                    </CardContent>
                </Card>
            </div>

            <div v-if="canViewCallsSection || canViewTablesSection" class="grid gap-4 xl:grid-cols-[340px_1fr]">
                <Card v-if="canViewCallsSection" class="border-slate-200 shadow-sm">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <BellRing class="h-4 w-4" />
                            {{ locale === 'tr' ? 'Bekleyen Garson Çağrıları' : 'Pending Waiter Calls' }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="restaurantPendingCalls.length === 0" class="text-sm text-muted-foreground">
                            {{ locale === 'tr' ? 'Bekleyen çağrı yok.' : 'No pending waiter calls.' }}
                        </div>
                        <div v-else class="space-y-2">
                            <article v-for="call in restaurantPendingCalls" :key="call.id" class="rounded-md border border-slate-200 bg-white p-3">
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

                <Card v-if="canViewTablesSection" class="border-slate-200 shadow-sm">
                    <CardHeader>
                        <CardTitle class="text-base">{{ locale === 'tr' ? 'Masa Siparişleri' : 'Table Orders' }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-3 md:grid-cols-2">
                            <article v-for="table in restaurantTables" :key="table.id" class="rounded-md border border-slate-200 bg-white p-3">
                                <div class="mb-2 text-sm">
                                    <p class="font-semibold">{{ table.name || table.table_number }}</p>
                                    <p class="text-xs text-muted-foreground">{{ locale === 'tr' ? 'Masa' : 'Table' }}: {{ table.table_number }}</p>
                                    <p class="text-xs text-muted-foreground">{{ locale === 'tr' ? 'Son sipariş' : 'Last order' }}: {{ table.last_order?.order_code || '-' }}</p>
                                </div>
                                <Link v-if="table.last_order?.id" :href="`/warehouse/restaurant-orders/${table.last_order.id}`">
                                    <Button variant="outline" size="sm" class="w-full">{{ locale === 'tr' ? 'Detay' : 'View' }}</Button>
                                </Link>
                            </article>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
