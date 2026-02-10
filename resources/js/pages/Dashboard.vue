<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    Package,
    TrendingDown,
    ArrowRightLeft,
    DollarSign,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { useI18n } from 'vue-i18n';
import { index as stockMovementsIndex } from '@/actions/App/Http/Controllers/Warehouse/StockMovementController';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Chart as ChartJS, ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title } from 'chart.js';
import { Pie, Bar } from 'vue-chartjs';


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
}

const props = defineProps<Props>();

const { t, locale } = useI18n();
const localeField = (x: string) => (x === 'tr' ? 'name_tr' : 'name_en');
const breadcrumbs: BreadcrumbItem[] = [
    { title: t('nav.dashboard'), href: dashboard().url },
];

const chartDataMovementType = computed(() => {
    const data = props.movementsByType || {};
    const labels = Object.keys(data).map(type => {
        const typeMap: Record<string, string> = {
            'in': t('nav.input'),
            'out': t('nav.output'),
            'transfer': t('nav.transfer'),
            'adjustment': t('nav.adjustment'),
        };
        return typeMap[type] || type;
    });
    
    return {
        labels,
        datasets: [
            {
                label: t('stockMovements.title'),
                data: Object.values(data),
                backgroundColor: [
                    '#10b981',
                    '#ef4444',
                    '#3b82f6',
                    '#f59e0b',
                ],
                borderColor: [
                    '#059669',
                    '#dc2626',
                    '#1d4ed8',
                    '#d97706',
                ],
                borderWidth: 2,
            },
        ],
    };
});

const chartDataWarehouse = computed(() => {
    const warehouseData: Record<string, number> = {};
    
    props.recentMovements?.forEach(m => {
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

</script>

<template>
    <Head :title="t('dashboard.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Key Metrics Section -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card class="border-l-4 border-l-emerald-500">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-3">
                        <div>
                            <CardTitle class="text-sm font-medium text-muted-foreground">
                                {{ t('dashboard.totalProducts') }}
                            </CardTitle>
                            <div class="text-2xl font-bold mt-2">{{ totalProducts }}</div>
                        </div>
                        <Package class="h-8 w-8 text-emerald-500 opacity-80" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            {{ t('stock.product') }}
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-rose-500">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-3">
                        <div>
                            <CardTitle class="text-sm font-medium text-muted-foreground">
                                {{ t('dashboard.lowStock') }}
                            </CardTitle>
                            <div class="text-2xl font-bold mt-2">{{ lowStockCount }}</div>
                        </div>
                        <TrendingDown class="h-8 w-8 text-rose-500 opacity-80" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            {{ t('stock.lowStock') }}
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-blue-500">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-3">
                        <div>
                            <CardTitle class="text-sm font-medium text-muted-foreground">
                                {{ t('dashboard.totalValue') }}
                            </CardTitle>
                            <div class="text-2xl font-bold mt-2">
                                ${{ (Number(totalValue) / 1000).toFixed(1) }}K
                            </div>
                        </div>
                        <DollarSign class="h-8 w-8 text-blue-500 opacity-80" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            Inventory value
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-amber-500">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-3">
                        <div>
                            <CardTitle class="text-sm font-medium text-muted-foreground">
                                {{ t('stockMovements.title') }}
                            </CardTitle>
                            <div class="text-2xl font-bold mt-2">{{ recentMovements?.length || 0 }}</div>
                        </div>
                        <ArrowRightLeft class="h-8 w-8 text-amber-500 opacity-80" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            Recent activity
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Charts Section -->
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
                        <p v-else class="text-sm text-muted-foreground text-center py-8">{{ t('dashboard.noMovements') }}</p>
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
                        <p v-else class="text-sm text-muted-foreground text-center py-8">{{ t('dashboard.noMovements') }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Activity Section -->
            <Card class="shadow-sm">
                <CardHeader class="border-b">
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-base">{{ t('dashboard.recentMovements') }}</CardTitle>
                            <CardDescription>
                                Latest stock movements
                            </CardDescription>
                        </div>
                        <Link :href="stockMovementsIndex.url()">
                            <Button variant="outline" size="sm">
                                {{ t('common.viewAll') }}
                            </Button>
                        </Link>
                    </div>
                </CardHeader>
                <CardContent class="pt-6">
                    <div v-if="recentMovements?.length" class="space-y-3">
                        <div
                            v-for="m in recentMovements.slice(0, 5)"
                            :key="m.id"
                            class="flex items-center justify-between p-3 rounded-lg border border-border hover:bg-muted/30 transition-colors"
                        >
                            <div class="flex items-center gap-3 flex-1">
                                <div :class="`p-2 rounded-lg ${
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
                                    <p class="font-medium text-sm">
                                        {{ (m.product as any)?.[locale === 'tr' ? 'name_tr' : 'name_en'] || '-' }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ (m.warehouse as any)?.[locale === 'tr' ? 'name_tr' : 'name_en'] || '-' }} · 
                                        <span :class="{
                                            'text-emerald-600 dark:text-emerald-400': m.type === 'in',
                                            'text-rose-600 dark:text-rose-400': m.type === 'out',
                                            'text-blue-600 dark:text-blue-400': m.type === 'transfer'
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
                    <p v-else class="text-sm text-muted-foreground text-center py-8">
                        {{ t('dashboard.noMovements') }}
                    </p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
