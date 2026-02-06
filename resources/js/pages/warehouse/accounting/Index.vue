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
                                    placeholder="e.g., Office supplies"
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
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="start_date">{{ t('common.startDate') }}</Label>
                            <Input id="start_date" type="date" :value="startDate" @change="(e) => updateDate('start_date', e.target.value)" />
                        </div>
                        <div class="space-y-2">
                            <Label for="end_date">{{ t('common.endDate') }}</Label>
                            <Input id="end_date" type="date" :value="endDate" @change="(e) => updateDate('end_date', e.target.value)" />
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
import { useI18n } from 'vue-i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { ref } from 'vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { TrendingUp, TrendingDown, DollarSign, Plus, Download, MoreHorizontal, Package } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';

const { t } = useI18n();

const props = defineProps<{
    entries: any;
    income: number;
    expenses: number;
    balance: number;
    stockValuation: number;
    walletBalance: number;
    walletInput: number;
    walletOutput: number;
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

function updateDate(field: string, value: string) {
    router.get('/warehouse/accounting', {
        start_date: field === 'start_date' ? value : props.startDate,
        end_date: field === 'end_date' ? value : props.endDate,
    });
}

function deleteEntry(id: number) {
    if (confirm(t('common.confirmDelete'))) {
        router.delete(`/warehouse/accounting/${id}`);
    }
}

function addWalletTransaction() {
    if (!walletAmount.value || !walletDescription.value) {
        alert('Please fill all required fields');
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
            alert('Error adding transaction');
            isSubmitting.value = false;
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
}
</script>
