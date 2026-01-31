<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
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
}

defineProps<Props>();

const { t } = useI18n();
const locale = (x: string) => (x === 'tr' ? 'name_tr' : 'name_en');
const breadcrumbs: BreadcrumbItem[] = [
    { title: t('nav.dashboard'), href: dashboard().url },
];
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ t('dashboard.lowStock') }}
                        </CardTitle>
                        <TrendingDown class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ lowStockCount }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ t('stock.lowStock') }}
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ t('dashboard.totalProducts') }}
                        </CardTitle>
                        <Package class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ totalProducts }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ t('dashboard.totalValue') }}
                        </CardTitle>
                        <DollarSign class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ Number(totalValue).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('dashboard.recentMovements') }}</CardTitle>
                    <CardDescription>
                        {{ t('stockMovements.title') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="recentMovements?.length" class="space-y-4">
                        <div
                            v-for="m in recentMovements"
                            :key="m.id"
                            class="flex items-center justify-between border-b pb-3 last:border-0 last:pb-0"
                        >
                            <div class="flex items-center gap-3">
                                <ArrowRightLeft class="h-4 w-4 text-muted-foreground" />
                                <div>
                                    <p class="font-medium">
                                        {{ (m.product as any)?.[$i18n.locale === 'tr' ? 'name_tr' : 'name_en'] || '-' }}
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ (m.warehouse as any)?.[$i18n.locale === 'tr' ? 'name_tr' : 'name_en'] || '-' }}
                                        · {{ m.type }} · {{ m.quantity }}
                                    </p>
                                </div>
                            </div>
                            <Badge variant="secondary">{{ m.movement_date }}</Badge>
                        </div>
                    </div>
                    <p v-else class="text-muted-foreground">
                        {{ t('dashboard.noMovements') }}
                    </p>
                    <Link :href="stockMovementsIndex.url()">
                        <Button variant="outline" class="mt-4">
                            {{ t('stockMovements.title') }}
                        </Button>
                    </Link>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
