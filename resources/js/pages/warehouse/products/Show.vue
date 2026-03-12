<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed, ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { index as productsIndex } from '@/actions/App/Http/Controllers/Warehouse/ProductController';
import AppPageContent from '@/components/AppPageContent.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    product: {
        id: number;
        name_tr: string;
        name_en: string;
        sku: string | null;
        barcode: string | null;
        category?: { name_tr: string; name_en: string };
        unit?: { symbol: string };
        unit_price?: number;
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
const locale = computed(() =>
    useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en',
);
const breadcrumbs: BreadcrumbItem[] = [
    { title: t('nav.products'), href: productsIndex.url() },
    { title: props.product[locale.value] || props.product.name_tr, href: '#' },
];

// Price history state
const priceHistory = ref<
    Array<{
        id: number;
        previous_price: number | null;
        new_price: number;
        reason: string | null;
        created_at: string;
    }>
>([]);
const currentPrice = ref<number>(props.product.unit_price ?? 0);
const isLoadingPriceHistory = ref(false);
const chartWidth = 720;
const chartHeight = 220;
const chartPadding = 24;

const chartData = computed(() =>
    [...priceHistory.value]
        .map((item) => ({
            id: item.id,
            value: Number(item.new_price),
            created_at: item.created_at,
        }))
        .filter((item) => Number.isFinite(item.value))
        .sort(
            (a, b) =>
                new Date(a.created_at).getTime() -
                new Date(b.created_at).getTime(),
        ),
);

const chartMin = computed(() => {
    if (!chartData.value.length) return 0;
    return Math.min(...chartData.value.map((item) => item.value));
});

const chartMax = computed(() => {
    if (!chartData.value.length) return 1;
    return Math.max(...chartData.value.map((item) => item.value));
});

const chartRange = computed(() => {
    const range = chartMax.value - chartMin.value;
    return range === 0 ? 1 : range;
});

const pointX = (index: number) => {
    const innerWidth = chartWidth - chartPadding * 2;
    if (chartData.value.length <= 1) return chartPadding;

    return chartPadding + (index * innerWidth) / (chartData.value.length - 1);
};

const pointY = (value: number) => {
    const innerHeight = chartHeight - chartPadding * 2;

    return (
        chartHeight -
        chartPadding -
        ((value - chartMin.value) / chartRange.value) * innerHeight
    );
};

const chartPoints = computed(() =>
    chartData.value
        .map((item, idx) => `${pointX(idx)},${pointY(item.value)}`)
        .join(' '),
);

// Load price history on mount
onMounted(async () => {
    isLoadingPriceHistory.value = true;
    try {
        const response = await fetch(
            `/warehouse/products/${props.product.id}/price-history`,
        );
        if (response.ok) {
            const data = await response.json();
            priceHistory.value = data.history || [];
            currentPrice.value =
                data.current_price ?? props.product.unit_price ?? 0;
        }
    } catch (error) {
        console.error('Failed to load price history:', error);
    } finally {
        isLoadingPriceHistory.value = false;
    }
});
</script>

<template>
    <Head :title="(product as any)[locale] || product.name_tr" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div
                    class="flex flex-row items-center justify-between gap-4 p-4 pb-0 md:p-6"
                >
                    <div>
                        <h1 class="text-xl font-semibold">
                            {{ (product as any)[locale] || product.name_tr }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            {{
                                product.sku ||
                                product.barcode ||
                                t('products.title')
                            }}
                        </p>
                    </div>
                    <Link :href="productsIndex.url()">
                        <Button variant="outline"
                            ><ArrowLeft class="mr-2 h-4 w-4" />{{
                                t('common.back')
                            }}</Button
                        >
                    </Link>
                </div>
            </template>
            <div
                class="flex flex-1 flex-col gap-6 overflow-y-auto p-4 pt-4 md:p-6"
            >
                <!-- Current stock by warehouse -->
                <div>
                    <h2 class="mb-2 text-sm font-medium">
                        {{ t('products.currentStock') }}
                    </h2>
                    <Table class="bg-transparent">
                        <TableHeader>
                            <TableRow
                                class="border-transparent hover:bg-transparent"
                            >
                                <TableHead class="text-muted-foreground">{{
                                    t('stock.warehouse')
                                }}</TableHead>
                                <TableHead class="text-muted-foreground">{{
                                    t('common.quantity')
                                }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="b in stockBalances"
                                :key="b.id"
                                class="border-transparent hover:bg-transparent"
                            >
                                <TableCell>{{
                                    (b.warehouse as any)?.[locale] ?? '-'
                                }}</TableCell>
                                <TableCell class="font-medium">{{
                                    Number(b.quantity).toString()
                                }}</TableCell>
                            </TableRow>
                            <TableRow
                                v-if="!stockBalances.length"
                                class="border-transparent hover:bg-transparent"
                            >
                                <TableCell
                                    colspan="2"
                                    class="text-muted-foreground"
                                    >{{ t('stock.outOfStock') }}</TableCell
                                >
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Price History -->
                <div>
                    <h2 class="mb-2 text-sm font-medium">
                        {{ t('products.priceHistory') }}
                    </h2>
                    <div
                        v-if="isLoadingPriceHistory"
                        class="text-sm text-muted-foreground"
                    >
                        {{ t('common.loading') || 'Loading...' }}
                    </div>
                    <div
                        v-else-if="!priceHistory.length"
                        class="text-sm text-muted-foreground"
                    >
                        {{ t('products.noPriceHistory') }}
                    </div>
                    <div v-else class="mb-4 rounded-lg border p-3">
                        <h3
                            class="mb-2 text-xs font-medium text-muted-foreground"
                        >
                            {{ t('products.priceHistoryChart') }}
                        </h3>
                        <svg
                            :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
                            class="h-52 w-full"
                        >
                            <line
                                :x1="chartPadding"
                                :y1="chartHeight - chartPadding"
                                :x2="chartWidth - chartPadding"
                                :y2="chartHeight - chartPadding"
                                stroke="currentColor"
                                class="text-muted-foreground/40"
                            />
                            <line
                                :x1="chartPadding"
                                :y1="chartPadding"
                                :x2="chartPadding"
                                :y2="chartHeight - chartPadding"
                                stroke="currentColor"
                                class="text-muted-foreground/40"
                            />
                            <polyline
                                :points="chartPoints"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                class="text-emerald-600"
                            />
                            <circle
                                v-for="(point, idx) in chartData"
                                :key="point.id"
                                :cx="pointX(idx)"
                                :cy="pointY(point.value)"
                                r="3.5"
                                class="fill-emerald-600"
                            />
                        </svg>
                    </div>
                    <Table v-if="priceHistory.length" class="bg-transparent">
                        <TableHeader>
                            <TableRow
                                class="border-transparent hover:bg-transparent"
                            >
                                <TableHead class="text-muted-foreground">{{
                                    t('common.date')
                                }}</TableHead>
                                <TableHead class="text-muted-foreground">{{
                                    t('products.previousPrice')
                                }}</TableHead>
                                <TableHead class="text-muted-foreground">{{
                                    t('products.currentPrice')
                                }}</TableHead>
                                <TableHead class="text-muted-foreground">{{
                                    t('products.reason')
                                }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="history in priceHistory"
                                :key="history.id"
                                class="border-transparent hover:bg-transparent"
                            >
                                <TableCell class="text-sm whitespace-nowrap">{{
                                    new Date(
                                        history.created_at,
                                    ).toLocaleString()
                                }}</TableCell>
                                <TableCell class="font-medium">
                                    {{
                                        history.previous_price === null
                                            ? '-'
                                            : Number(
                                                  history.previous_price,
                                              ).toFixed(2)
                                    }}
                                </TableCell>
                                <TableCell
                                    class="font-medium text-green-600 dark:text-green-400"
                                    >{{
                                        Number(history.new_price).toFixed(2)
                                    }}</TableCell
                                >
                                <TableCell class="text-sm">{{
                                    history.reason || '-'
                                }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div
                        v-if="priceHistory.length"
                        class="mt-3 text-xs text-muted-foreground"
                    >
                        {{ t('products.currentPrice') }}:
                        <strong>{{ Number(currentPrice).toFixed(2) }}</strong>
                    </div>
                </div>

                <!-- Product activity logs -->
                <div>
                    <h2 class="mb-2 text-sm font-medium">
                        {{ t('activityLogs.title') }}
                    </h2>
                    <Table class="bg-transparent">
                        <TableHeader>
                            <TableRow
                                class="border-transparent hover:bg-transparent"
                            >
                                <TableHead class="text-muted-foreground">{{
                                    t('common.date')
                                }}</TableHead>
                                <TableHead class="text-muted-foreground">{{
                                    t('activityLogs.action')
                                }}</TableHead>
                                <TableHead class="text-muted-foreground">{{
                                    t('activityLogs.description')
                                }}</TableHead>
                                <TableHead class="text-muted-foreground">{{
                                    t('activityLogs.user')
                                }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="log in logs"
                                :key="log.id"
                                class="border-transparent hover:bg-transparent"
                            >
                                <TableCell class="text-sm whitespace-nowrap">{{
                                    new Date(log.created_at).toLocaleString()
                                }}</TableCell>
                                <TableCell
                                    ><Badge variant="secondary">{{
                                        log.action
                                    }}</Badge></TableCell
                                >
                                <TableCell class="max-w-xs truncate text-sm">{{
                                    log.description || '-'
                                }}</TableCell>
                                <TableCell class="text-sm">{{
                                    log.user?.name ?? '-'
                                }}</TableCell>
                            </TableRow>
                            <TableRow
                                v-if="!logs.length"
                                class="border-transparent hover:bg-transparent"
                            >
                                <TableCell
                                    colspan="4"
                                    class="text-muted-foreground"
                                    >{{ t('activityLogs.noLogs') }}</TableCell
                                >
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>
        </AppPageContent>
    </AppLayout>
</template>
