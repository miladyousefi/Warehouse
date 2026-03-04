<template>
    <Head :title="t('accounting.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <!-- Summary Cards -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">{{ t('accounting.income') }}</CardTitle>
                        <TrendingUp class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-emerald-600">
                            {{ Number(income).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">{{ t('accounting.expenses') }}</CardTitle>
                        <TrendingDown class="h-4 w-4 text-rose-600 dark:text-rose-400" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-rose-600">
                            {{ Number(expenses).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">{{ t('accounting.balance') }}</CardTitle>
                        <DollarSign class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                    </CardHeader>
                    <CardContent>
                        <div :class="`text-2xl font-bold ${balance >= 0 ? 'text-emerald-600' : 'text-rose-600'}`">
                            {{ Number(balance).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">{{ t('accounting.stockValuation') }}</CardTitle>
                        <Package class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-blue-600">
                            {{ Number(stockValuation).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Wallet Section with Quick Add -->
            <div class="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('accounting.wallet') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-4">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground mb-2">{{ t('accounting.walletBalance') }}</p>
                                <p :class="`text-3xl font-bold ${walletBalance >= 0 ? 'text-emerald-600' : 'text-rose-600'}`">
                                    {{ Number(walletBalance).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                                </p>
                            </div>
                            <div class="grid gap-2 pt-2 border-t">
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground">{{ t('accounting.walletInput') }}</span>
                                    <span class="font-semibold text-emerald-600">{{ Number(walletInput).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground">{{ t('accounting.walletOutput') }}</span>
                                    <span class="font-semibold text-rose-600">{{ Number(walletOutput).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">{{ t('accounting.addTransaction') || 'Add Transaction' }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div>
                                <Label class="mb-2 block text-sm">{{ t('accounting.transactionType') || 'Type' }}</Label>
                                <div class="flex gap-2">
                                    <Button
                                        :variant="walletType === 'input' ? 'default' : 'outline'"
                                        size="sm"
                                        class="flex-1"
                                        @click="walletType = 'input'"
                                    >
                                        {{ t('accounting.walletInput') }}
                                    </Button>
                                    <Button
                                        :variant="walletType === 'output' ? 'default' : 'outline'"
                                        size="sm"
                                        class="flex-1"
                                        @click="walletType = 'output'"
                                    >
                                        {{ t('accounting.walletOutput') }}
                                    </Button>
                                </div>
                            </div>

                            <div>
                                <Label for="wallet_amount" class="text-sm">{{ t('common.amount') }}</Label>
                                <Input
                                    id="wallet_amount"
                                    v-model="walletAmount"
                                    type="number"
                                    step="0.01"
                                    placeholder="0.00"
                                    class="mt-1"
                                />
                            </div>

                            <div>
                                <Label for="wallet_description" class="text-sm">{{ t('common.description') }}</Label>
                                <Input
                                    id="wallet_description"
                                    v-model="walletDescription"
                                    :placeholder="t('accounting.descriptionPlaceholder') || 'e.g., Office supplies'"
                                    class="mt-1"
                                />
                            </div>

                            <Button
                                class="w-full"
                                :disabled="isSubmitting || !walletAmount || !walletDescription"
                                @click="addWalletTransaction"
                            >
                                {{ isSubmitting ? t('common.saving') : t('common.save') }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Orders Accounting -->
            <div class="grid gap-4 md:grid-cols-5">
                <Card class="md:col-span-1">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">Orders</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ Number(orderStats?.orders_count || 0).toLocaleString() }}</div>
                    </CardContent>
                </Card>
                <Card class="md:col-span-1">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">Order Sales</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-xl font-bold text-emerald-600">{{ Number(orderStats?.gross_sales || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</div>
                    </CardContent>
                </Card>
                <Card class="md:col-span-1">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">Order Cost (est.)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-xl font-bold text-rose-600">{{ Number(orderStats?.estimated_cost || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</div>
                    </CardContent>
                </Card>
                <Card class="md:col-span-1">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">Order Profit</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div :class="`text-xl font-bold ${Number(orderStats?.gross_profit || 0) >= 0 ? 'text-blue-600' : 'text-rose-600'}`">
                            {{ Number(orderStats?.gross_profit || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                        </div>
                    </CardContent>
                </Card>
                <Card class="md:col-span-1">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">Paid Orders</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ Number(orderStats?.paid_orders || 0).toLocaleString() }}</div>
                    </CardContent>
                </Card>
            </div>

            <Card v-if="orderDaily?.length">
                <CardHeader>
                    <CardTitle>Order Sales Trend</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div v-for="row in orderDaily" :key="row.day" class="grid grid-cols-[110px_1fr_120px] items-center gap-3 text-sm">
                            <div class="text-muted-foreground">{{ new Date(row.day).toLocaleDateString() }}</div>
                            <div class="h-2 rounded bg-muted">
                                <div class="h-2 rounded bg-emerald-500" :style="{ width: `${salesBarWidth(row.sales_total)}%` }" />
                            </div>
                            <div class="text-right font-medium">{{ Number(row.sales_total).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">Last Month Orders</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ Number(lastMonthOrderStats?.orders_count || 0).toLocaleString() }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">Last Month Sales</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-emerald-600">
                            {{ Number(lastMonthOrderStats?.sales_total || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">Warehouse Out Value</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-amber-600">
                            {{ Number(warehouseOutValue || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <Card v-if="dailyFlow?.length">
                    <CardHeader>
                        <CardTitle>Income vs Expense (Flow)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div style="height: 280px">
                            <Line :data="flowChartData" :options="chartOptions" />
                        </div>
                    </CardContent>
                </Card>
                <Card v-if="orderDaily?.length || warehouseOutDaily?.length">
                    <CardHeader>
                        <CardTitle>Orders vs Warehouse Out</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div style="height: 280px">
                            <Bar :data="opsChartData" :options="chartOptions" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Advanced Analytics -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">{{ t('accounting.priceUpdates') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ (priceHistoryStats?.total_changes ?? 0).toLocaleString() }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">{{ t('accounting.avgPriceChangePct') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ Number(priceHistoryStats?.avg_change_pct ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}%
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">{{ t('accounting.priceDirection') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-sm">
                            <div>{{ t('accounting.priceIncreased') }}: <strong>{{ priceHistoryStats?.increased_count ?? 0 }}</strong></div>
                            <div>{{ t('accounting.priceDecreased') }}: <strong>{{ priceHistoryStats?.decreased_count ?? 0 }}</strong></div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card v-if="dailyFlow?.length">
                <CardHeader>
                    <CardTitle>{{ t('accounting.dailyFlow') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{{ t('common.date') }}</TableHead>
                                <TableHead class="text-right">{{ t('accounting.income') }}</TableHead>
                                <TableHead class="text-right">{{ t('accounting.expenses') }}</TableHead>
                                <TableHead class="text-right">{{ t('accounting.balance') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="row in dailyFlow" :key="row.day">
                                <TableCell>{{ new Date(row.day).toLocaleDateString() }}</TableCell>
                                <TableCell class="text-right text-emerald-600">{{ Number(row.income_total).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</TableCell>
                                <TableCell class="text-right text-rose-600">{{ Number(row.expense_total).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</TableCell>
                                <TableCell class="text-right font-semibold">{{ Number(row.income_total - row.expense_total).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <Card v-if="priceHistoryStats?.top_products?.length">
                <CardHeader>
                    <CardTitle>{{ t('accounting.topPriceVolatilityProducts') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{{ t('common.name') }}</TableHead>
                                <TableHead class="text-right">{{ t('accounting.priceUpdates') }}</TableHead>
                                <TableHead class="text-right">{{ t('accounting.avgPriceDelta') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="row in priceHistoryStats.top_products" :key="row.product_id">
                                <TableCell>{{ row.product_name }}</TableCell>
                                <TableCell class="text-right">{{ row.changes_count }}</TableCell>
                                <TableCell class="text-right">{{ Number(row.avg_delta).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Filter and Action Bar -->
            <Card>
                <CardHeader>
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <CardTitle>{{ t('accounting.entries') }}</CardTitle>
                        </div>
                        <div class="flex gap-2">
                            <Link :href="`/warehouse/accounting/export?start_date=${startDate}&end_date=${endDate}`">
                                <Button variant="outline" size="sm">
                                    <Download class="h-4 w-4 mr-2" />
                                    {{ t('common.export') }}
                                </Button>
                            </Link>
                            <Link :href="`/warehouse/accounting/export?dataset=orders&format=csv&start_date=${startDate}&end_date=${endDate}`">
                                <Button variant="outline" size="sm">Export Orders CSV</Button>
                            </Link>
                            <Link :href="`/warehouse/accounting/export?dataset=orders&format=xlsx&start_date=${startDate}&end_date=${endDate}`">
                                <Button variant="outline" size="sm">Export Orders XLSX</Button>
                            </Link>
                            <Link href="/warehouse/accounting/create">
                                <Button size="sm">
                                    <Plus class="h-4 w-4 mr-2" />
                                    {{ t('common.add') }}
                                </Button>
                            </Link>
                        </div>
                    </div>
                </CardHeader>

                <CardContent>
                    <div class="grid gap-4 md:grid-cols-4">
                        <div class="space-y-2">
                            <Label for="year_filter">Year</Label>
                            <select id="year_filter" v-model="filterYear" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option value="">All Years</option>
                                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="month_filter">Month</Label>
                            <select id="month_filter" v-model="filterMonth" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option v-for="m in months" :key="m.value || 'all'" :value="m.value">{{ m.label }}</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="start_date">{{ t('common.startDate') }}</Label>
                            <Input id="start_date" type="date" v-model="filterStartDate" />
                        </div>
                        <div class="space-y-2">
                            <Label for="end_date">{{ t('common.endDate') }}</Label>
                            <Input id="end_date" type="date" v-model="filterEndDate" />
                        </div>
                        <div class="md:col-span-4 flex justify-end">
                            <Button size="sm" @click="applyFilters">Apply Filters</Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Entries Table -->
            <Card>
                <CardContent class="pt-6">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{{ t('common.date') }}</TableHead>
                                <TableHead>{{ t('common.type') }}</TableHead>
                                <TableHead>{{ t('common.category') }}</TableHead>
                                <TableHead>{{ t('common.description') }}</TableHead>
                                <TableHead class="text-right">{{ t('common.amount') }}</TableHead>
                                <TableHead class="text-right">{{ t('common.actions') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="entry in entries.data" :key="entry.id">
                                <TableCell>{{ new Date(entry.date).toLocaleDateString() }}</TableCell>
                                <TableCell>
                                    <Badge :variant="entry.type === 'income' ? 'outline' : 'destructive'">
                                        {{ entry.type }}
                                    </Badge>
                                </TableCell>
                                <TableCell>{{ entry.category }}</TableCell>
                                <TableCell>{{ entry.description }}</TableCell>
                                <TableCell :class="`text-right font-semibold ${entry.type === 'income' ? 'text-emerald-600' : 'text-rose-600'}`">
                                    {{ entry.type === 'income' ? '+' : '-' }}{{ Number(entry.amount).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="icon">
                                                <MoreHorizontal class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem as-child>
                                                <Link :href="`/warehouse/accounting/${entry.id}/edit`">
                                                    {{ t('common.edit') }}
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem as-child>
                                                <button @click="deleteEntry(entry.id)" class="w-full text-left text-destructive">
                                                    {{ t('common.delete') }}
                                                </button>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="entries.last_page > 1" class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    {{ $t('common.showing') }} {{ entries.from }} {{ $t('common.to') }} {{ entries.to }} {{ $t('common.of') }} {{ entries.total }}
                </p>
                <div class="flex gap-2">
                    <Link v-if="entries.prev_page_url" :href="entries.prev_page_url">
                        <Button variant="outline" size="sm">{{ t('common.previous') }}</Button>
                    </Link>
                    <Link v-if="entries.next_page_url" :href="entries.next_page_url">
                        <Button size="sm">{{ t('common.next') }}</Button>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { TrendingUp, TrendingDown, DollarSign, Plus, Download, MoreHorizontal, Package } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Bar, Line } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend } from 'chart.js';
import { useI18n } from 'vue-i18n';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

const { t } = useI18n();
ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend);

const props = defineProps<{
    entries: any;
    income: number;
    expenses: number;
    balance: number;
    stockValuation: number;
    walletBalance: number;
    walletInput: number;
    walletOutput: number;
    dailyFlow: Array<{ day: string; income_total: number; expense_total: number }>;
    priceHistoryStats: {
        total_changes: number;
        avg_change_pct: number;
        increased_count: number;
        decreased_count: number;
        top_products: Array<{ product_id: number; product_name: string; changes_count: number; avg_delta: number }>;
    };
    orderStats?: {
        orders_count: number;
        paid_orders: number;
        gross_sales: number;
        estimated_cost: number;
        gross_profit: number;
    };
    orderDaily?: Array<{ day: string; orders_count: number; sales_total: number }>;
    warehouseOutValue?: number;
    warehouseOutDaily?: Array<{ day: string; out_total: number }>;
    lastMonthOrderStats?: {
        start_date: string;
        end_date: string;
        orders_count: number;
        sales_total: number;
    };
    selectedYear?: string | null;
    selectedMonth?: string | null;
    startDate: string;
    endDate: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('nav.accounting'), href: '/warehouse/accounting' },
];

// Wallet transaction form state
const walletAmount = ref('');
const walletDescription = ref('');
const walletType = ref<'input' | 'output'>('input');
const isSubmitting = ref(false);
const filterYear = ref<string>(props.selectedYear || '');
const filterMonth = ref<string>(props.selectedMonth || '');
const filterStartDate = ref<string>(props.startDate);
const filterEndDate = ref<string>(props.endDate);
const maxOrderSales = computed(() => Math.max(...(props.orderDaily || []).map((x) => Number(x.sales_total || 0)), 0));
const currentYear = new Date().getFullYear();
const years = Array.from({ length: 7 }, (_, i) => String(currentYear - 5 + i));
const months = [
    { value: '', label: 'All Months' },
    { value: '1', label: 'January' },
    { value: '2', label: 'February' },
    { value: '3', label: 'March' },
    { value: '4', label: 'April' },
    { value: '5', label: 'May' },
    { value: '6', label: 'June' },
    { value: '7', label: 'July' },
    { value: '8', label: 'August' },
    { value: '9', label: 'September' },
    { value: '10', label: 'October' },
    { value: '11', label: 'November' },
    { value: '12', label: 'December' },
];

const flowChartData = computed(() => ({
    labels: (props.dailyFlow || []).map((x) => new Date(x.day).toLocaleDateString()),
    datasets: [
        {
            label: 'Income',
            data: (props.dailyFlow || []).map((x) => Number(x.income_total || 0)),
            borderColor: '#10b981',
            backgroundColor: 'rgba(16,185,129,0.15)',
            tension: 0.3,
        },
        {
            label: 'Expenses',
            data: (props.dailyFlow || []).map((x) => Number(x.expense_total || 0)),
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239,68,68,0.15)',
            tension: 0.3,
        },
    ],
}));

const opsChartData = computed(() => ({
    labels: (props.orderDaily || []).map((x) => new Date(x.day).toLocaleDateString()),
    datasets: [
        {
            label: 'Order Sales',
            data: (props.orderDaily || []).map((x) => Number(x.sales_total || 0)),
            backgroundColor: '#3b82f6',
        },
        {
            label: 'Warehouse Out Value',
            data: (props.warehouseOutDaily || []).map((x) => Number(x.out_total || 0)),
            backgroundColor: '#f59e0b',
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: true, position: 'bottom' as const } },
};

function salesBarWidth(value: number): number {
    const max = maxOrderSales.value;
    if (max <= 0) return 0;
    return Math.max(4, Math.round((Number(value || 0) / max) * 100));
}

function applyFilters() {
    router.get('/warehouse/accounting', {
        year: filterYear.value || undefined,
        month: filterMonth.value || undefined,
        start_date: filterStartDate.value || undefined,
        end_date: filterEndDate.value || undefined,
    }, { preserveState: true });
}

function deleteEntry(id: number) {
    if (confirm(t('common.confirmDelete'))) {
        router.delete(`/warehouse/accounting/${id}`);
    }
}

function addWalletTransaction() {
    if (!walletAmount.value || !walletDescription.value) {
        alert(t('common.fillRequired'));
        return;
    }

    isSubmitting.value = true;

    router.post('/warehouse/accounting', {
        date: new Date().toISOString().split('T')[0],
        type: walletType.value === 'input' ? 'income' : 'expense',
        category: walletType.value === 'input' ? 'wallet_input' : 'wallet_output',
        description: walletDescription.value,
        amount: walletAmount.value,
        notes: '',
    }, {
        onSuccess: () => {
            walletAmount.value = '';
            walletDescription.value = '';
        },
        onError: () => {
            alert(t('common.error'));
            isSubmitting.value = false;
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
}
</script>
