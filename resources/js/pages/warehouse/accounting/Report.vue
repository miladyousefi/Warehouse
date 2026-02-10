<template>
    <Head :title="t('accounting.report')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Date Range Filter & Export -->
            <Card class="border-0 shadow-sm">
                <CardHeader class="flex flex-row items-center justify-between pb-4 border-b">
                    <CardTitle class="text-lg">{{ t('common.dateRange') }}</CardTitle>
                    <div class="flex gap-2">
                        <Button variant="outline" size="sm" @click="exportToExcel" class="gap-2">
                            <Download class="h-4 w-4" />
                            Excel
                        </Button>
                        <Button variant="outline" size="sm" @click="exportToPDF" class="gap-2">
                            <Download class="h-4 w-4" />
                            PDF
                        </Button>
                    </div>
                </CardHeader>
                <CardContent class="pt-6">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="start_date" class="text-sm font-medium">{{ t('common.startDate') }}</Label>
                            <Input id="start_date" type="date" :value="startDate" @change="(e) => updateDate('start_date', (e.target as HTMLInputElement).value)" class="h-10" />
                        </div>
                        <div class="space-y-2">
                            <Label for="end_date" class="text-sm font-medium">{{ t('common.endDate') }}</Label>
                            <Input id="end_date" type="date" :value="endDate" @change="(e) => updateDate('end_date', (e.target as HTMLInputElement).value)" class="h-10" />
                        </div>
                        <div class="flex items-end">
                            <Button @click="applyFilters" class="w-full">{{ t('common.apply') }}</Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Summary Cards -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card class="border-l-4 border-l-emerald-500 shadow-sm">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium text-muted-foreground">{{ t('accounting.totalIncome') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-end justify-between">
                            <div>
                                <p class="text-3xl font-bold text-emerald-600">{{ totalIncome }}</p>
                                <p class="text-xs text-muted-foreground mt-1">Total incoming value</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-rose-500 shadow-sm">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium text-muted-foreground">{{ t('accounting.totalExpense') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-end justify-between">
                            <div>
                                <p class="text-3xl font-bold text-rose-600">{{ totalExpense }}</p>
                                <p class="text-xs text-muted-foreground mt-1">Total outgoing value</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card :class="`border-l-4 shadow-sm ${netIncome >= 0 ? 'border-l-blue-500' : 'border-l-orange-500'}`">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium text-muted-foreground">{{ t('accounting.balance') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-end justify-between">
                            <div>
                                <p :class="`text-3xl font-bold ${netIncome >= 0 ? 'text-blue-600' : 'text-orange-600'}`">
                                    {{ formatCurrency(netIncome) }}
                                </p>
                                <p class="text-xs text-muted-foreground mt-1">Net result</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Charts Section -->
            <div class="grid gap-4 md:grid-cols-2">
                <Card class="shadow-sm">
                    <CardHeader class="pb-4 border-b">
                        <CardTitle class="text-base">{{ t('accounting.monthlyIncome') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="pt-6">
                        <div v-if="monthlyIncome.length > 0" style="height: 300px">
                            <Line :data="incomeChartData" :options="chartOptions" />
                        </div>
                        <p v-else class="text-sm text-muted-foreground text-center py-8">{{ t('common.noData') }}</p>
                    </CardContent>
                </Card>

                <Card class="shadow-sm">
                    <CardHeader class="pb-4 border-b">
                        <CardTitle class="text-base">{{ t('accounting.monthlyExpense') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="pt-6">
                        <div v-if="monthlyExpense.length > 0" style="height: 300px">
                            <Line :data="expenseChartData" :options="chartOptions" />
                        </div>
                        <p v-else class="text-sm text-muted-foreground text-center py-8">{{ t('common.noData') }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Income vs Expense Comparison -->
            <Card v-if="monthlyIncome.length > 0 && monthlyExpense.length > 0" class="shadow-sm">
                <CardHeader class="pb-4 border-b">
                    <CardTitle class="text-base">{{ t('accounting.incomeVsExpense') }}</CardTitle>
                </CardHeader>
                <CardContent class="pt-6">
                    <div style="height: 350px">
                        <Bar :data="comparisonChartData" :options="comparisonChartOptions" />
                    </div>
                </CardContent>
            </Card>

            <!-- Category Breakdown (if available) -->
            <Card v-if="categoryData && categoryData.length > 0" class="shadow-sm">
                <CardHeader class="pb-4 border-b">
                    <CardTitle class="text-base">{{ t('accounting.categories') }}</CardTitle>
                </CardHeader>
                <CardContent class="pt-6">
                    <div style="height: 350px">
                        <Doughnut :data="categoryChartData" :options="chartOptions" />
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Download } from 'lucide-vue-next';
import { Line, Bar, Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, BarElement, ArcElement, Title, Tooltip, Legend } from 'chart.js';
import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';
import html2canvas from 'html2canvas';
import { type BreadcrumbItem } from '@/types';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, ArcElement, Title, Tooltip, Legend);

const { t } = useI18n();

const props = defineProps<{
    monthlyIncome: Array<{ month: string; total: number }>;
    monthlyExpense: Array<{ month: string; total: number }>;
    categoryData?: Array<{ name: string; total: number }>;
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
    plugins: {
        legend: {
            display: true,
            position: 'bottom' as const,
        },
    },
};

const comparisonChartOptions = {
    ...chartOptions,
    scales: {
        y: {
            beginAtZero: true,
        },
    },
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
    return formatCurrency(props.monthlyIncome.reduce((sum, d) => sum + (d.total || 0), 0));
});

const totalExpense = computed(() => {
    return formatCurrency(props.monthlyExpense.reduce((sum, d) => sum + (d.total || 0), 0));
});

const netIncome = computed(() => {
    const income = props.monthlyIncome.reduce((sum, d) => sum + (d.total || 0), 0);
    const expense = props.monthlyExpense.reduce((sum, d) => sum + (d.total || 0), 0);
    return income - expense;
});

const categoryChartData = computed(() => ({
    labels: props.categoryData?.map(d => d.name) || [],
    datasets: [{
        label: t('common.amount'),
        data: props.categoryData?.map(d => d.total) || [],
        backgroundColor: [
            '#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'
        ],
    }],
}));

async function exportToExcel() {
    const ws_data: any[] = [];
    
    // Header
    ws_data.push(['Accounting Report', '', '', '']);
    ws_data.push([`From ${props.startDate} to ${props.endDate}`, '', '', '']);
    ws_data.push(['', '', '', '']);
    
    // Summary
    ws_data.push(['Summary', '', '', '']);
    ws_data.push(['Total Income', parseFloat(String(props.monthlyIncome.reduce((sum, d) => sum + (d.total || 0), 0))).toFixed(2), '', '']);
    ws_data.push(['Total Expense', parseFloat(String(props.monthlyExpense.reduce((sum, d) => sum + (d.total || 0), 0))).toFixed(2), '', '']);
    ws_data.push(['Net Income', netIncome.value.toFixed(2), '', '']);
    ws_data.push(['', '', '', '']);
    
    // Monthly Income
    ws_data.push(['Month', 'Income', '', '']);
    props.monthlyIncome.forEach(d => {
        ws_data.push([d.month, d.total.toFixed(2), '', '']);
    });
    ws_data.push(['', '', '', '']);
    
    // Monthly Expense
    ws_data.push(['Month', 'Expense', '', '']);
    props.monthlyExpense.forEach(d => {
        ws_data.push([d.month, d.total.toFixed(2), '', '']);
    });
    
    const ws = XLSX.utils.aoa_to_sheet(ws_data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Report');
    XLSX.writeFile(wb, `Accounting_Report_${props.startDate}_to_${props.endDate}.xlsx`);
}

async function exportToPDF() {
    const element = document.body;
    const canvas = await html2canvas(element, { scale: 2, bgcolor: '#ffffff' });
    const imgData = canvas.toDataURL('image/png');
    const pdf = new jsPDF();
    const imgWidth = 210; // A4 width in mm
    const imgHeight = (canvas.height * imgWidth) / canvas.width;
    
    pdf.addImage(imgData, 'PNG', 0, 0, imgWidth, imgHeight);
    pdf.save(`Accounting_Report_${props.startDate}_to_${props.endDate}.pdf`);
}

function formatCurrency(value: number): string {
    return value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

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
