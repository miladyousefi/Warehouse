<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    DollarSign,
    Download,
    MoreHorizontal,
    Plus,
    TrendingDown,
    TrendingUp,
    Wallet,
} from 'lucide-vue-next';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AppPageContent from '@/components/AppPageContent.vue';
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

const { t, locale } = useI18n();

const props = defineProps<{
    entries: any;
    income: number;
    expenses: number;
    balance: number;
    stockValuation: number;
    walletBalance: number;
    walletInput: number;
    walletOutput: number;
    orderStats?: {
        orders_count: number;
        paid_orders: number;
        gross_sales: number;
        estimated_cost: number;
        gross_profit: number;
    };
    selectedYear?: string | null;
    selectedMonth?: string | null;
    startDate: string;
    endDate: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('nav.accounting'), href: '/warehouse/accounting' },
];

const filterFieldClass =
    'h-11 rounded-md border border-border/70 bg-white/10 px-3 text-sm backdrop-blur-md shadow-none focus-visible:ring-2 focus-visible:ring-ring/50 dark:bg-white/5';

const filterSelectClass =
    'h-11 w-full rounded-md border border-border/70 bg-white/10 px-3 text-sm backdrop-blur-md shadow-none focus:outline-none focus:ring-2 focus:ring-ring/50 dark:bg-white/5';

const walletAmount = ref('');
const walletDescription = ref('');
const walletType = ref<'input' | 'output'>('input');
const isSubmitting = ref(false);
const filterYear = ref<string>(props.selectedYear || '');
const filterMonth = ref<string>(props.selectedMonth || '');
const filterStartDate = ref<string>(props.startDate);
const filterEndDate = ref<string>(props.endDate);
const currentYear = new Date().getFullYear();
const years = Array.from({ length: 7 }, (_, i) => String(currentYear - 5 + i));

const months =
    locale.value === 'tr'
        ? [
              { value: '', label: 'Tüm Aylar' },
              { value: '1', label: 'Ocak' },
              { value: '2', label: 'Şubat' },
              { value: '3', label: 'Mart' },
              { value: '4', label: 'Nisan' },
              { value: '5', label: 'Mayıs' },
              { value: '6', label: 'Haziran' },
              { value: '7', label: 'Temmuz' },
              { value: '8', label: 'Ağustos' },
              { value: '9', label: 'Eylül' },
              { value: '10', label: 'Ekim' },
              { value: '11', label: 'Kasım' },
              { value: '12', label: 'Aralık' },
          ]
        : [
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

function applyFilters() {
    router.get(
        '/warehouse/accounting',
        {
            year: filterYear.value || undefined,
            month: filterMonth.value || undefined,
            start_date: filterStartDate.value || undefined,
            end_date: filterEndDate.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
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

    router.post(
        '/warehouse/accounting',
        {
            date: new Date().toISOString().split('T')[0],
            type: walletType.value === 'input' ? 'income' : 'expense',
            category:
                walletType.value === 'input' ? 'wallet_input' : 'wallet_output',
            description: walletDescription.value,
            amount: walletAmount.value,
            notes: '',
        },
        {
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
        },
    );
}
</script>

<template>
    <Head :title="t('accounting.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="mx-auto w-full max-w-7xl p-4 pb-0 md:p-6 md:pb-0">
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
                    >
                        <div class="space-y-1">
                            <h1 class="text-xl font-semibold">
                                {{ t('nav.accounting') }}
                            </h1>
                            <p class="text-sm text-muted-foreground">
                                {{ t('accounting.title') }}
                            </p>
                        </div>
                        <div
                            class="flex flex-wrap items-center gap-2 sm:justify-end"
                        >
                            <Link
                                :href="`/warehouse/accounting/export?start_date=${startDate}&end_date=${endDate}`"
                            >
                                <Button variant="outline" size="sm">
                                    <Download class="mr-2 h-4 w-4" />
                                    <span class="hidden sm:inline">
                                        {{ t('common.export') }}
                                    </span>
                                    <span class="sm:hidden">
                                        {{ t('common.export') }}
                                    </span>
                                </Button>
                            </Link>
                            <Link href="/warehouse/accounting/create">
                                <Button size="sm">
                                    <Plus class="mr-2 h-4 w-4" />
                                    {{ t('common.add') }}
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </template>

            <div class="mx-auto w-full max-w-7xl space-y-4 p-4 md:p-6">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader
                            class="flex flex-row items-center justify-between pb-2"
                        >
                            <CardTitle class="text-sm font-medium">{{
                                t('accounting.income')
                            }}</CardTitle>
                            <TrendingUp class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold text-emerald-600">
                                {{
                                    Number(income).toLocaleString(undefined, {
                                        minimumFractionDigits: 2,
                                    })
                                }}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader
                            class="flex flex-row items-center justify-between pb-2"
                        >
                            <CardTitle class="text-sm font-medium">{{
                                t('accounting.expenses')
                            }}</CardTitle>
                            <TrendingDown
                                class="h-4 w-4 text-muted-foreground"
                            />
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold text-rose-600">
                                {{
                                    Number(expenses).toLocaleString(undefined, {
                                        minimumFractionDigits: 2,
                                    })
                                }}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader
                            class="flex flex-row items-center justify-between pb-2"
                        >
                            <CardTitle class="text-sm font-medium">{{
                                t('accounting.balance')
                            }}</CardTitle>
                            <DollarSign class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <p
                                :class="`text-2xl font-bold ${balance >= 0 ? 'text-emerald-600' : 'text-rose-600'}`"
                            >
                                {{
                                    Number(balance).toLocaleString(undefined, {
                                        minimumFractionDigits: 2,
                                    })
                                }}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader
                            class="flex flex-row items-center justify-between pb-2"
                        >
                            <CardTitle class="text-sm font-medium">{{
                                t('accounting.walletBalance')
                            }}</CardTitle>
                            <Wallet class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <p
                                :class="`text-2xl font-bold ${walletBalance >= 0 ? 'text-emerald-600' : 'text-rose-600'}`"
                            >
                                {{
                                    Number(walletBalance).toLocaleString(
                                        undefined,
                                        { minimumFractionDigits: 2 },
                                    )
                                }}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    <Card class="lg:col-span-2">
                        <CardHeader>
                            <div
                                class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <CardTitle>
                                    {{ t('accounting.entries') }}
                                </CardTitle>
                                <p class="text-xs text-muted-foreground">
                                    {{ startDate }} → {{ endDate }}
                                </p>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div
                                class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
                            >
                                <div class="space-y-2">
                                    <Label for="year_filter">{{
                                        locale === 'tr' ? 'Yıl' : 'Year'
                                    }}</Label>
                                    <select
                                        id="year_filter"
                                        v-model="filterYear"
                                        :class="filterSelectClass"
                                    >
                                        <option value="">
                                            {{
                                                locale === 'tr'
                                                    ? 'Tüm Yıllar'
                                                    : 'All Years'
                                            }}
                                        </option>
                                        <option
                                            v-for="y in years"
                                            :key="y"
                                            :value="y"
                                        >
                                            {{ y }}
                                        </option>
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <Label for="month_filter">{{
                                        locale === 'tr' ? 'Ay' : 'Month'
                                    }}</Label>
                                    <select
                                        id="month_filter"
                                        v-model="filterMonth"
                                        :class="filterSelectClass"
                                    >
                                        <option
                                            v-for="m in months"
                                            :key="m.value || 'all'"
                                            :value="m.value"
                                        >
                                            {{ m.label }}
                                        </option>
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <Label for="start_date">{{
                                        t('common.startDate')
                                    }}</Label>
                                    <Input
                                        id="start_date"
                                        type="date"
                                        v-model="filterStartDate"
                                        :class="filterFieldClass"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label for="end_date">{{
                                        t('common.endDate')
                                    }}</Label>
                                    <Input
                                        id="end_date"
                                        type="date"
                                        v-model="filterEndDate"
                                        :class="filterFieldClass"
                                    />
                                </div>
                                <div class="flex md:col-span-4">
                                    <Button
                                        size="sm"
                                        class="w-full sm:ml-auto sm:w-auto"
                                        @click="applyFilters"
                                    >
                                        {{
                                            locale === 'tr'
                                                ? 'Filtrele'
                                                : 'Apply Filters'
                                        }}
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">{{
                                t('accounting.addTransaction') ||
                                (locale === 'tr'
                                    ? 'Hızlı İşlem Ekle'
                                    : 'Quick Transaction')
                            }}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div class="grid grid-cols-2 gap-2">
                                    <Button
                                        :variant="
                                            walletType === 'input'
                                                ? 'default'
                                                : 'outline'
                                        "
                                        size="sm"
                                        @click="walletType = 'input'"
                                    >
                                        {{ t('accounting.walletInput') }}
                                    </Button>
                                    <Button
                                        :variant="
                                            walletType === 'output'
                                                ? 'default'
                                                : 'outline'
                                        "
                                        size="sm"
                                        @click="walletType = 'output'"
                                    >
                                        {{ t('accounting.walletOutput') }}
                                    </Button>
                                </div>
                                <div class="space-y-2">
                                    <Label for="wallet_amount">{{
                                        t('common.amount')
                                    }}</Label>
                                    <Input
                                        id="wallet_amount"
                                        v-model="walletAmount"
                                        type="number"
                                        step="0.01"
                                        placeholder="0.00"
                                        class="h-11"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label for="wallet_description">{{
                                        t('common.description')
                                    }}</Label>
                                    <Input
                                        id="wallet_description"
                                        v-model="walletDescription"
                                        :placeholder="
                                            t(
                                                'accounting.descriptionPlaceholder',
                                            ) || 'Note'
                                        "
                                        class="h-11"
                                    />
                                </div>
                                <div
                                    class="rounded-md border border-border/70 bg-white/10 p-3 text-xs text-muted-foreground backdrop-blur-md dark:bg-white/5"
                                >
                                    {{ t('accounting.walletInput') }}:
                                    {{
                                        Number(walletInput).toLocaleString(
                                            undefined,
                                            { minimumFractionDigits: 2 },
                                        )
                                    }}
                                    <br />
                                    {{ t('accounting.walletOutput') }}:
                                    {{
                                        Number(walletOutput).toLocaleString(
                                            undefined,
                                            { minimumFractionDigits: 2 },
                                        )
                                    }}
                                    <br />
                                    {{ t('accounting.stockValuation') }}:
                                    {{
                                        Number(stockValuation).toLocaleString(
                                            undefined,
                                            { minimumFractionDigits: 2 },
                                        )
                                    }}
                                </div>
                                <Button
                                    class="w-full"
                                    :disabled="
                                        isSubmitting ||
                                        !walletAmount ||
                                        !walletDescription
                                    "
                                    @click="addWalletTransaction"
                                >
                                    {{
                                        isSubmitting
                                            ? t('common.saving')
                                            : t('common.save')
                                    }}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <div
                            class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <CardTitle class="text-base">
                                {{ t('accounting.entries') }}
                            </CardTitle>
                            <div class="text-xs text-muted-foreground">
                                {{ entries.total }}
                                {{ locale === 'tr' ? 'kayıt' : 'items' }}
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="whitespace-nowrap">
                                        {{ t('common.date') }}
                                    </TableHead>
                                    <TableHead class="whitespace-nowrap">
                                        {{ t('common.type') }}
                                    </TableHead>
                                    <TableHead class="hidden md:table-cell">{{
                                        t('common.category')
                                    }}</TableHead>
                                    <TableHead>{{
                                        t('common.description')
                                    }}</TableHead>
                                    <TableHead class="text-right">{{
                                        t('common.amount')
                                    }}</TableHead>
                                    <TableHead class="text-right">{{
                                        t('common.actions')
                                    }}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="entry in entries.data"
                                    :key="entry.id"
                                >
                                    <TableCell class="whitespace-nowrap">{{
                                        new Date(
                                            entry.date,
                                        ).toLocaleDateString()
                                    }}</TableCell>
                                    <TableCell>
                                        <Badge
                                            :variant="
                                                entry.type === 'income'
                                                    ? 'outline'
                                                    : 'destructive'
                                            "
                                        >
                                            {{ entry.type }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="hidden md:table-cell">{{
                                        entry.category
                                    }}</TableCell>
                                    <TableCell>
                                        <div class="font-medium">
                                            {{ entry.description }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground md:hidden"
                                        >
                                            {{ entry.category }}
                                        </div>
                                    </TableCell>
                                    <TableCell
                                        :class="`text-right font-semibold ${entry.type === 'income' ? 'text-emerald-600' : 'text-rose-600'}`"
                                    >
                                        {{ entry.type === 'income' ? '+' : '-'
                                        }}{{
                                            Number(entry.amount).toLocaleString(
                                                undefined,
                                                { minimumFractionDigits: 2 },
                                            )
                                        }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                >
                                                    <MoreHorizontal
                                                        class="h-4 w-4"
                                                    />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem as-child>
                                                    <Link
                                                        :href="`/warehouse/accounting/${entry.id}/edit`"
                                                    >
                                                        {{ t('common.edit') }}
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem as-child>
                                                    <button
                                                        @click="
                                                            deleteEntry(
                                                                entry.id,
                                                            )
                                                        "
                                                        class="w-full text-left text-destructive"
                                                    >
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

                <div
                    v-if="entries.last_page > 1"
                    class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-sm text-muted-foreground">
                        {{ $t('common.showing') }} {{ entries.from }}
                        {{ $t('common.to') }} {{ entries.to }}
                        {{ $t('common.of') }}
                        {{ entries.total }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-if="entries.prev_page_url"
                            :href="entries.prev_page_url"
                        >
                            <Button variant="outline" size="sm">{{
                                t('common.previous')
                            }}</Button>
                        </Link>
                        <Link
                            v-if="entries.next_page_url"
                            :href="entries.next_page_url"
                        >
                            <Button size="sm">{{ t('common.next') }}</Button>
                        </Link>
                    </div>
                </div>
            </div>
        </AppPageContent>
    </AppLayout>
</template>
