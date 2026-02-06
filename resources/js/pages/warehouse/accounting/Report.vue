<template>
    <Head :title="t('accounting.report')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <!-- Date Range Filter -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('common.dateRange') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="start_date">{{ t('common.startDate') }}</Label>
                            <Input id="start_date" type="date" :value="startDate" @change="(e) => updateDate('start_date', e.target.value)" />
                        </div>
                        <div class="space-y-2">
                            <Label for="end_date">{{ t('common.endDate') }}</Label>
                            <Input id="end_date" type="date" :value="endDate" @change="(e) => updateDate('end_date', e.target.value)" />
                        </div>
                        <div class="flex items-end">
                            <Button @click="applyFilters">{{ t('common.apply') }}</Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Monthly Charts -->
            <div class="grid gap-4 md:grid-cols-2">
                <Card v-if="monthlyIncome.length > 0">
                    <CardHeader>
                        <CardTitle>{{ t('accounting.monthlyIncome') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div style="height: 300px">
                            <Line :data="incomeChartData" :options="chartOptions" />
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="monthlyExpense.length > 0">
                    <CardHeader>
                        <CardTitle>{{ t('accounting.monthlyExpense') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div style="height: 300px">
                            <Line :data="expenseChartData" :options="chartOptions" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Income vs Expense Comparison -->
            <Card v-if="monthlyIncome.length > 0 && monthlyExpense.length > 0">
                <CardHeader>
                    <CardTitle>{{ t('accounting.incomeVsExpense') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div style="height: 400px">
                        <Bar :data="comparisonChartData" :options="chartOptions" />
                    </div>
                </CardContent>
            </Card>

            <!-- Summary -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('common.summary') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <p class="text-sm text-muted-foreground">{{ t('accounting.totalIncome') }}</p>
                            <p class="text-2xl font-bold text-emerald-600">{{ totalIncome }}</p>
                        </div>
                        <div class="space-y-2">
                            <p class="text-sm text-muted-foreground">{{ t('accounting.totalExpense') }}</p>
                            <p class="text-2xl font-bold text-rose-600">{{ totalExpense }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Line, Bar } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend } from 'chart.js';
import { type BreadcrumbItem } from '@/types';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend);

const { t } = useI18n();

const props = defineProps<{
    monthlyIncome: Array<{ month: string; total: number }>;
    monthlyExpense: Array<{ month: string; total: number }>;
    startDate: string;
    endDate: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('nav.accounting'), href: '/warehouse/accounting' },
    { title: t('accounting.report') },
];

const chartOptions = {
    responsive: true,
    maintainAspectRatio: true,
};

const incomeChartData = computed(() => ({
    labels: props.monthlyIncome.map(d => d.month),
    datasets: [{
        label: t('accounting.income'),
        data: props.monthlyIncome.map(d => d.total),
        borderColor: '#10b981',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        tension: 0.4,
    }],
}));

const expenseChartData = computed(() => ({
    labels: props.monthlyExpense.map(d => d.month),
    datasets: [{
        label: t('accounting.expenses'),
        data: props.monthlyExpense.map(d => d.total),
        borderColor: '#ef4444',
        backgroundColor: 'rgba(239, 68, 68, 0.1)',
        tension: 0.4,
    }],
}));

const incomeMap = computed(() => {
    const map: Record<string, number> = {};
    props.monthlyIncome.forEach(d => {
        map[d.month] = d.total;
    });
    return map;
});

const expenseMap = computed(() => {
    const map: Record<string, number> = {};
    props.monthlyExpense.forEach(d => {
        map[d.month] = d.total;
    });
    return map;
});

const allMonths = computed(() => {
    const months = new Set([
        ...props.monthlyIncome.map(d => d.month),
        ...props.monthlyExpense.map(d => d.month),
    ]);
    return Array.from(months).sort();
});

const comparisonChartData = computed(() => ({
    labels: allMonths.value,
    datasets: [
        {
            label: t('accounting.income'),
            data: allMonths.value.map(m => incomeMap.value[m] || 0),
            backgroundColor: '#10b981',
        },
        {
            label: t('accounting.expenses'),
            data: allMonths.value.map(m => expenseMap.value[m] || 0),
            backgroundColor: '#ef4444',
        },
    ],
}));

const totalIncome = computed(() => {
    return props.monthlyIncome.reduce((sum, d) => sum + (d.total || 0), 0).toLocaleString(undefined, { minimumFractionDigits: 2 });
});

const totalExpense = computed(() => {
    return props.monthlyExpense.reduce((sum, d) => sum + (d.total || 0), 0).toLocaleString(undefined, { minimumFractionDigits: 2 });
});

function updateDate(field: string, value: string) {
    router.get('/warehouse/accounting/report', {
        start_date: field === 'start_date' ? value : props.startDate,
        end_date: field === 'end_date' ? value : props.endDate,
    });
}

function applyFilters() {
    // Filters are applied automatically via updateDate
}
</script>
