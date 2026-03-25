<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    CategoryScale,
    Chart as ChartJS,
    ArcElement,
    BarElement,
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip,
    type ChartData,
    type ChartOptions,
} from 'chart.js';
import {
    ArrowRightLeft,
    ClipboardList,
    Database,
    DollarSign,
    GitPullRequest,
    Package,
    TriangleAlert,
    Truck,
    Users,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Bar, Doughnut, Line } from 'vue-chartjs';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

ChartJS.register(
    CategoryScale,
    LinearScale,
    ArcElement,
    BarElement,
    PointElement,
    LineElement,
    Tooltip,
    Legend,
    Filler,
);

interface Props {
    lowStockCount: number;
    totalProducts: number;
    totalValue: number;
    totalMovementsCount: number;
    movementsTodayCount: number;
    movementTrend: {
        labels: string[];
        totals: number[];
        byType: Record<string, number[]>;
    };
    activeSuppliersCount: number;
    activeWarehousesCount: number;
    usersCount: number;
    openPurchaseOrdersCount: number;
    movementsByType?: Record<string, number>;
    movementsByWarehouse?: Array<{
        warehouse_id: number;
        name_tr: string;
        name_en: string;
        count: number;
    }>;
    stockValueByWarehouse?: Array<{
        warehouse_id: number;
        name_tr: string;
        name_en: string;
        value: number;
    }>;
    canRunGitPull?: boolean;
}

const props = defineProps<Props>();
const { t, locale } = useI18n();
const mounted = ref(true);
const themeTick = ref(0);

function readCssVar(name: string, fallback: string): string {
    if (typeof window === 'undefined') return fallback;
    const v = getComputedStyle(document.documentElement)
        .getPropertyValue(name)
        .trim();
    return v || fallback;
}

function withAlpha(color: string, alpha: number): string {
    const c = color.trim();
    if (c.startsWith('hsl(') && c.endsWith(')')) {
        const inner = c.slice(4, -1).trim();
        const base = inner.split('/')[0]?.trim() || inner;
        const parts = base.replace(/,/g, ' ').split(/\s+/).filter(Boolean);
        if (parts.length >= 3) {
            const [h, s, l] = parts;
            return `hsla(${h}, ${s}, ${l}, ${alpha})`;
        }
    }
    if (c.startsWith('rgb(') && c.endsWith(')')) {
        const inner = c.slice(4, -1).trim();
        const base = inner.split('/')[0]?.trim() || inner;
        const parts = base.replace(/,/g, ' ').split(/\s+/).filter(Boolean);
        if (parts.length >= 3) {
            const [r, g, b] = parts;
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }
    }
    return c;
}

const themeColors = computed(() => {
    // Make this computed re-run when dark mode toggles.
    void themeTick.value;

    return {
        background: readCssVar('--background', 'hsl(40 20% 98%)'),
        foreground: readCssVar('--foreground', 'hsl(24 10% 10%)'),
        border: readCssVar('--border', 'hsl(40 15% 88%)'),
        mutedForeground: readCssVar('--muted-foreground', 'hsl(24 8% 45%)'),
        popover: readCssVar('--popover', 'hsl(0 0% 100%)'),
        popoverForeground: readCssVar(
            '--popover-foreground',
            'hsl(24 10% 10%)',
        ),
        primary: readCssVar('--primary', 'hsl(24 95% 44%)'),
        chart1: readCssVar('--chart-1', 'hsl(24 95% 50%)'),
        chart2: readCssVar('--chart-2', 'hsl(142 71% 38%)'),
        chart3: readCssVar('--chart-3', 'hsl(197 37% 24%)'),
        chart4: readCssVar('--chart-4', 'hsl(43 96% 56%)'),
        chart5: readCssVar('--chart-5', 'hsl(27 87% 60%)'),
    };
});

onMounted(() => {
    if (typeof window === 'undefined') return;
    const obs = new MutationObserver(() => {
        themeTick.value += 1;
    });
    obs.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
    onUnmounted(() => obs.disconnect());
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('nav.dashboard'), href: dashboard().url },
];

function runGitPull() {
    if (typeof window !== 'undefined') {
        const confirmed = window.confirm(
            locale.value === 'tr'
                ? 'Proje guncellensin mi? Bu islem git pull, composer update ve php artisan migrate komutlarini calistirir ve biraz surebilir.'
                : 'Update this project? This will run git pull, composer update, and php artisan migrate, and it may take a while.',
        );

        if (!confirmed) return;
    }

    router.post(
        '/warehouse/dashboard/git-pull',
        {},
        {
            preserveScroll: true,
        },
    );
}

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

const movementTrendLabels = computed(() =>
    (props.movementTrend?.labels ?? []).map((d) => {
        const raw = String(d ?? '');
        const ymd = raw.slice(0, 10);
        const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(ymd);
        if (!m) return raw;

        const year = Number(m[1]);
        const month = Number(m[2]);
        const day = Number(m[3]);
        const date = new Date(Date.UTC(year, month - 1, day));
        if (Number.isNaN(date.getTime())) return raw;

        return new Intl.DateTimeFormat(
            locale.value === 'tr' ? 'tr-TR' : 'en-US',
            { month: 'short', day: '2-digit', timeZone: 'UTC' },
        ).format(date);
    }),
);

const movementTrendChartData = computed<ChartData<'line'>>(() => ({
    labels: movementTrendLabels.value,
    datasets: [
        {
            label: locale.value === 'tr' ? 'Toplam' : 'Total',
            data: props.movementTrend?.totals ?? [],
            tension: 0.35,
            borderWidth: 2,
            pointRadius: 2,
            pointHoverRadius: 4,
            fill: true,
            borderColor: withAlpha(themeColors.value.chart4, 0.95),
            pointBackgroundColor: withAlpha(themeColors.value.chart4, 0.95),
            backgroundColor: (ctx) => {
                const chart = ctx.chart;
                const { chartArea } = chart;
                if (!chartArea)
                    return withAlpha(themeColors.value.chart4, 0.12);
                const gradient = chart.ctx.createLinearGradient(
                    0,
                    chartArea.top,
                    0,
                    chartArea.bottom,
                );
                gradient.addColorStop(
                    0,
                    withAlpha(themeColors.value.chart4, 0.35),
                );
                gradient.addColorStop(
                    1,
                    withAlpha(themeColors.value.chart4, 0.02),
                );
                return gradient;
            },
        },
        {
            label: locale.value === 'tr' ? 'Giriş' : 'In',
            data: props.movementTrend?.byType?.in ?? [],
            tension: 0.35,
            borderWidth: 2,
            pointRadius: 0,
            fill: false,
            borderColor: withAlpha(themeColors.value.chart1, 0.8),
        },
        {
            label: locale.value === 'tr' ? 'Çıkış' : 'Out',
            data: props.movementTrend?.byType?.out ?? [],
            tension: 0.35,
            borderWidth: 2,
            pointRadius: 0,
            fill: false,
            borderColor: withAlpha(themeColors.value.chart3, 0.7),
        },
    ],
}));

const movementTrendChartOptions = computed<ChartOptions<'line'>>(() => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    plugins: {
        legend: {
            display: true,
            labels: {
                usePointStyle: true,
                boxWidth: 6,
                boxHeight: 6,
            },
        },
        tooltip: {
            backgroundColor: withAlpha(themeColors.value.popover, 0.95),
            titleColor: themeColors.value.popoverForeground,
            bodyColor: themeColors.value.popoverForeground,
            borderColor: withAlpha(themeColors.value.primary, 0.35),
            borderWidth: 1,
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { maxTicksLimit: 8 },
        },
        y: {
            grid: {
                color: withAlpha(themeColors.value.mutedForeground, 0.22),
            },
            ticks: { precision: 0 },
        },
    },
}));

const movementTypeDonutData = computed<ChartData<'doughnut'>>(() => ({
    labels: movementTypeRows.value.map((r) => r.label),
    datasets: [
        {
            data: movementTypeRows.value.map((r) => r.value),
            backgroundColor: [
                withAlpha(themeColors.value.chart1, 0.85), // in
                withAlpha(themeColors.value.chart3, 0.75), // out
                withAlpha(themeColors.value.chart4, 0.85), // transfer
                withAlpha(themeColors.value.chart5, 0.75), // adjustment
            ],
            borderColor: withAlpha(themeColors.value.border, 0.65),
            borderWidth: 1,
        },
    ],
}));

const movementTypeDonutOptions = computed<ChartOptions<'doughnut'>>(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: { usePointStyle: true, boxWidth: 6, boxHeight: 6 },
        },
        tooltip: {
            backgroundColor: withAlpha(themeColors.value.popover, 0.95),
            titleColor: themeColors.value.popoverForeground,
            bodyColor: themeColors.value.popoverForeground,
        },
    },
}));

const movementsByWarehouseBarData = computed<ChartData<'bar'>>(() => {
    const rows = props.movementsByWarehouse ?? [];
    const labels = rows.map((r) =>
        locale.value === 'tr' ? r.name_tr : r.name_en,
    );
    return {
        labels,
        datasets: [
            {
                label: t('dashboard.movementsByWarehouse'),
                data: rows.map((r) => r.count),
                backgroundColor: withAlpha(themeColors.value.chart4, 0.45),
                borderColor: withAlpha(themeColors.value.chart4, 0.9),
                borderWidth: 1,
                borderRadius: 6,
            },
        ],
    };
});

const movementsByWarehouseBarOptions = computed<ChartOptions<'bar'>>(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: withAlpha(themeColors.value.popover, 0.95),
            titleColor: themeColors.value.popoverForeground,
            bodyColor: themeColors.value.popoverForeground,
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { maxTicksLimit: 6 },
        },
        y: {
            grid: {
                color: withAlpha(themeColors.value.mutedForeground, 0.22),
            },
            ticks: { precision: 0 },
        },
    },
}));

const stockValueByWarehouseBarData = computed<ChartData<'bar'>>(() => {
    const rows = props.stockValueByWarehouse ?? [];
    const labels = rows.map((r) =>
        locale.value === 'tr' ? r.name_tr : r.name_en,
    );
    return {
        labels,
        datasets: [
            {
                label: t('dashboard.stockValueByWarehouse'),
                data: rows.map((r) => Number(r.value) || 0),
                backgroundColor: withAlpha(themeColors.value.chart2, 0.4),
                borderColor: withAlpha(themeColors.value.chart2, 0.9),
                borderWidth: 1,
                borderRadius: 6,
            },
        ],
    };
});

const stockValueByWarehouseBarOptions = computed<ChartOptions<'bar'>>(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: withAlpha(themeColors.value.popover, 0.95),
            titleColor: themeColors.value.popoverForeground,
            bodyColor: themeColors.value.popoverForeground,
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { maxTicksLimit: 6 },
        },
        y: {
            grid: {
                color: withAlpha(themeColors.value.mutedForeground, 0.22),
            },
            ticks: { callback: (v) => Number(v).toLocaleString() },
        },
    },
}));
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="dash-page" :class="{ 'is-mounted': mounted }">
            <div
                class="mx-auto flex w-full max-w-7xl flex-col gap-5 p-4 md:p-6"
            >
                <section class="dash-animate" style="--delay: 0ms">
                    <div
                        class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between"
                    >
                        <div class="space-y-1">
                            <h1 class="dash-title">{{ t('nav.dashboard') }}</h1>
                            <p class="dash-subtitle">
                                {{
                                    locale === 'tr'
                                        ? 'Stok, değer ve hareketler için hızlı özet.'
                                        : 'A quick overview of stock, value, and activity.'
                                }}
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <a
                                href="/warehouse/dashboard/backup-sql"
                                class="inline-flex"
                            >
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="dash-outline-btn"
                                >
                                    <Database class="mr-2 h-4 w-4" />
                                    {{ t('dashboard.backupDatabase') }}
                                </Button>
                            </a>

                            <Button
                                v-if="canRunGitPull"
                                variant="outline"
                                size="sm"
                                class="dash-outline-btn"
                                @click="runGitPull"
                            >
                                <GitPullRequest class="mr-2 h-4 w-4" />
                                Update
                            </Button>
                        </div>
                    </div>
                </section>

                <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <Card class="dash-card dash-animate" style="--delay: 60ms">
                        <CardHeader
                            class="flex flex-row items-center justify-between pb-2"
                        >
                            <CardTitle class="text-sm text-muted-foreground">{{
                                t('dashboard.totalProducts')
                            }}</CardTitle>
                            <div class="dash-icon">
                                <Package class="h-4 w-4" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <p class="dash-metric">{{ totalProducts }}</p>
                            <p class="dash-hint">
                                {{
                                    locale === 'tr'
                                        ? 'Aktif ürün sayısı'
                                        : 'Active products'
                                }}
                            </p>
                        </CardContent>
                    </Card>

                    <Card class="dash-card dash-animate" style="--delay: 120ms">
                        <CardHeader
                            class="flex flex-row items-center justify-between pb-2"
                        >
                            <CardTitle class="text-sm text-muted-foreground">{{
                                t('dashboard.totalValue')
                            }}</CardTitle>
                            <div class="dash-icon">
                                <DollarSign class="h-4 w-4" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <p class="dash-metric">
                                {{
                                    Number(totalValue).toLocaleString(
                                        undefined,
                                        {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2,
                                        },
                                    )
                                }}
                            </p>
                            <p class="dash-hint">
                                {{
                                    locale === 'tr'
                                        ? 'Tahmini envanter değeri'
                                        : 'Estimated inventory value'
                                }}
                            </p>
                        </CardContent>
                    </Card>

                    <Card class="dash-card dash-animate" style="--delay: 180ms">
                        <CardHeader
                            class="flex flex-row items-center justify-between pb-2"
                        >
                            <CardTitle class="text-sm text-muted-foreground">{{
                                locale === 'tr' ? 'Düşük Stok' : 'Low Stock'
                            }}</CardTitle>
                            <div class="dash-icon dash-icon-warn">
                                <TriangleAlert class="h-4 w-4" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <p class="dash-metric">{{ lowStockCount }}</p>
                            <p class="dash-hint">
                                {{
                                    locale === 'tr'
                                        ? 'Minimum stoğun altındaki ürünler'
                                        : 'Products below minimum stock'
                                }}
                            </p>
                        </CardContent>
                    </Card>

                    <Card class="dash-card dash-animate" style="--delay: 240ms">
                        <CardHeader
                            class="flex flex-row items-center justify-between pb-2"
                        >
                            <CardTitle class="text-sm text-muted-foreground">{{
                                locale === 'tr'
                                    ? 'Bugün Hareket'
                                    : 'Movements Today'
                            }}</CardTitle>
                            <div class="dash-icon">
                                <ArrowRightLeft class="h-4 w-4" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <p class="dash-metric">{{ movementsTodayCount }}</p>
                            <p class="dash-hint">
                                {{
                                    locale === 'tr'
                                        ? `Toplam: ${totalMovementsCount}`
                                        : `Total: ${totalMovementsCount}`
                                }}
                            </p>
                        </CardContent>
                    </Card>

                    <Card class="dash-card dash-animate" style="--delay: 300ms">
                        <CardHeader
                            class="flex flex-row items-center justify-between pb-2"
                        >
                            <CardTitle class="text-sm text-muted-foreground">{{
                                locale === 'tr'
                                    ? 'Açık Satın Alma'
                                    : 'Open Purchase Orders'
                            }}</CardTitle>
                            <div class="dash-icon">
                                <ClipboardList class="h-4 w-4" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <p class="dash-metric">
                                {{ openPurchaseOrdersCount }}
                            </p>
                            <p class="dash-hint">
                                {{
                                    locale === 'tr'
                                        ? 'Taslak / gönderildi / kısmi'
                                        : 'Draft / sent / partial'
                                }}
                            </p>
                        </CardContent>
                    </Card>

                    <Card class="dash-card dash-animate" style="--delay: 360ms">
                        <CardHeader
                            class="flex flex-row items-center justify-between pb-2"
                        >
                            <CardTitle class="text-sm text-muted-foreground">{{
                                locale === 'tr'
                                    ? 'Tedarikçi · Depo · Kullanıcı'
                                    : 'Suppliers · Warehouses · Users'
                            }}</CardTitle>
                            <div class="dash-icon">
                                <Truck class="h-4 w-4" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <p class="dash-metric">
                                {{ activeSuppliersCount }}
                                <span class="dash-metric-sep">·</span>
                                {{ activeWarehousesCount }}
                                <span class="dash-metric-sep">·</span>
                                {{ usersCount }}
                            </p>
                            <p class="dash-hint">
                                <Users
                                    class="mr-1 inline-block h-3.5 w-3.5 align-[-2px] opacity-70"
                                />
                                {{
                                    locale === 'tr'
                                        ? 'Aktif tedarikçi ve depo'
                                        : 'Active suppliers and warehouses'
                                }}
                            </p>
                        </CardContent>
                    </Card>
                </section>

                <section class="grid gap-4 xl:grid-cols-[1.35fr_1fr]">
                    <Card class="dash-card dash-animate" style="--delay: 420ms">
                        <CardHeader>
                            <CardTitle class="text-base">{{
                                locale === 'tr'
                                    ? 'Hareket Trendleri'
                                    : 'Movement Trends'
                            }}</CardTitle>
                            <CardDescription>{{
                                locale === 'tr'
                                    ? 'Son 30 gün — toplam, giriş ve çıkış'
                                    : 'Last 30 days — total, in, and out'
                            }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="h-[280px] w-full">
                                <Line
                                    :data="movementTrendChartData"
                                    :options="movementTrendChartOptions"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="dash-card dash-animate" style="--delay: 480ms">
                        <CardHeader>
                            <CardTitle class="text-base">{{
                                t('dashboard.movementsByType')
                            }}</CardTitle>
                            <CardDescription>{{
                                locale === 'tr'
                                    ? 'Dağılım özeti'
                                    : 'Distribution summary'
                            }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div
                                v-if="movementTypeRows.length"
                                class="space-y-4"
                            >
                                <div class="h-[190px]">
                                    <Doughnut
                                        :data="movementTypeDonutData"
                                        :options="movementTypeDonutOptions"
                                    />
                                </div>
                                <div class="space-y-3">
                                    <div
                                        v-for="row in movementTypeRows"
                                        :key="row.key"
                                        class="space-y-1"
                                    >
                                        <div
                                            class="flex items-center justify-between text-sm"
                                        >
                                            <span class="font-medium">{{
                                                row.label
                                            }}</span>
                                            <span class="text-muted-foreground"
                                                >{{ row.value }} ({{
                                                    row.pct
                                                }}%)</span
                                            >
                                        </div>
                                        <div
                                            class="h-2 rounded-full bg-amber-50/70 ring-1 ring-amber-200/40"
                                        >
                                            <div
                                                class="h-2 rounded-full bg-gradient-to-r from-amber-500 to-amber-600 shadow-[0_8px_20px_rgba(245,158,11,0.35)]"
                                                :style="{
                                                    width: `${row.pct}%`,
                                                }"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-sm text-muted-foreground">
                                {{ t('dashboard.noMovements') }}
                            </p>
                        </CardContent>
                    </Card>
                </section>

                <section class="grid gap-4 xl:grid-cols-2">
                    <Card class="dash-card dash-animate" style="--delay: 520ms">
                        <CardHeader>
                            <CardTitle class="text-base">{{
                                t('dashboard.movementsByWarehouse')
                            }}</CardTitle>
                            <CardDescription>{{
                                locale === 'tr' ? 'Son 30 gün' : 'Last 30 days'
                            }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="h-[260px] w-full">
                                <Bar
                                    :data="movementsByWarehouseBarData"
                                    :options="movementsByWarehouseBarOptions"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="dash-card dash-animate" style="--delay: 560ms">
                        <CardHeader>
                            <CardTitle class="text-base">{{
                                t('dashboard.stockValueByWarehouse')
                            }}</CardTitle>
                            <CardDescription>{{
                                locale === 'tr'
                                    ? 'Tahmini envanter değeri'
                                    : 'Estimated inventory value'
                            }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="h-[260px] w-full">
                                <Bar
                                    :data="stockValueByWarehouseBarData"
                                    :options="stockValueByWarehouseBarOptions"
                                />
                            </div>
                        </CardContent>
                    </Card>
                </section>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.dash-title {
    font-family: 'Fraunces', Georgia, serif;
    font-size: 1.85rem;
    line-height: 1.1;
    font-weight: 600;
    letter-spacing: -0.02em;
    background: linear-gradient(
        135deg,
        var(--foreground) 0%,
        var(--primary) 100%
    );
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.dash-subtitle {
    color: var(--muted-foreground);
    font-size: 0.95rem;
}

.dash-card {
    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease;
}

.dash-card:hover {
    transform: translateY(-2px);
}

.dash-outline-btn {
    border-color: color-mix(
        in srgb,
        var(--primary) 35%,
        transparent
    ) !important;
    background: color-mix(
        in srgb,
        var(--background) 65%,
        transparent
    ) !important;
}

.dash-metric {
    font-size: 1.85rem;
    font-weight: 650;
    letter-spacing: -0.02em;
}

.dash-metric-sep {
    opacity: 0.35;
    padding: 0 0.25rem;
}

.dash-hint {
    margin-top: 0.25rem;
    font-size: 0.8rem;
    color: var(--muted-foreground);
}

.dash-icon {
    display: grid;
    place-items: center;
    width: 34px;
    height: 34px;
    border-radius: 12px;
    background: color-mix(in srgb, var(--primary) 12%, transparent);
    border: 1px solid color-mix(in srgb, var(--primary) 22%, transparent);
    color: var(--primary);
}

.dash-icon-warn {
    background: color-mix(in srgb, var(--destructive) 10%, transparent);
    border-color: color-mix(in srgb, var(--destructive) 20%, transparent);
}

.dash-row {
    border-radius: 12px;
    border: 1px solid rgba(180, 130, 50, 0.18);
    background: rgba(255, 255, 255, 0.75);
    padding: 0.75rem;
    backdrop-filter: blur(8px);
    transition:
        transform 0.18s ease,
        border-color 0.18s ease,
        box-shadow 0.18s ease;
}

.dash-row:hover {
    transform: translateY(-1px);
    border-color: rgba(245, 158, 11, 0.32);
    box-shadow: 0 12px 32px rgba(28, 21, 16, 0.06);
}

.dash-icon-btn {
    background: color-mix(in srgb, var(--primary) 10%, transparent) !important;
    border: 1px solid color-mix(in srgb, var(--primary) 25%, transparent) !important;
}

.dash-animate {
    opacity: 0;
    transform: translateY(16px);
    transition:
        opacity 0.65s ease,
        transform 0.65s ease;
    transition-delay: var(--delay, 0ms);
}

.is-mounted .dash-animate {
    opacity: 1;
    transform: translateY(0);
}

@media (max-width: 640px) {
    .dash-title {
        font-size: 1.55rem;
    }
}
</style>
