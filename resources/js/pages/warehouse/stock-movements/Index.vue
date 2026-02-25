<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Plus, Edit, Trash2, Download, FileText, ChevronDown } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { usePermission } from '@/composables/usePermission';
import { index, create } from '@/actions/App/Http/Controllers/Warehouse/StockMovementController';
import { type BreadcrumbItem } from '@/types';
import AppPageContent from '@/components/AppPageContent.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { formatTurkeyDate } from '@/composables/useTurkeyDate';

const props = defineProps<{
    movements: { data: Array<Record<string, unknown>>; links: Array<{ url: string | null; label: string }> };
    warehouses: Array<Record<string, unknown>>;
    suppliers: Array<Record<string, unknown>>;
}>();

const { t } = useI18n();
const { can } = usePermission();
const locale = computed(() => (useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en'));
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.stockMovements'), href: index.url() }];

// Form state
const deleteMovementId = ref<number | null>(null);
const form = useForm({});
const showFilters = ref(false);
const filterForm = useForm({
    warehouse_id: '',
    product_id: '',
    type: '',
    supplier_id: '',
    factor_number: '',
    date_from: '',
    date_to: '',
});

// Export state
const exportForm = useForm({});
const exportAllData = ref(true);
const isExporting = ref(false);

const toggleFilters = () => {
    showFilters.value = !showFilters.value;
};

const confirmDelete = (id: number) => {
    deleteMovementId.value = id;
};

const cancelDelete = () => {
    deleteMovementId.value = null;
};

const deleteMovement = async (id: number) => {
    form.delete(`/warehouse/stock-movements/${id}`, {
        onSuccess: () => {
            deleteMovementId.value = null;
        },
        onError: (errors) => {
            console.error('Delete error:', errors);
        },
    });
};

const applyFilters = () => {
    filterForm.get(route('warehouse.stock-movements.index'), {
        only: ['movements', 'suppliers', 'warehouses'],
    });
};

const resetFilters = () => {
    filterForm.reset();
    filterForm.get(route('warehouse.stock-movements.index'), {
        only: ['movements', 'suppliers', 'warehouses'],
    });
};

const exportToExcel = async () => {
    isExporting.value = true;
    try {
        // Get current URL query string (which contains applied filters)
        const currentUrl = new URL(window.location.href);
        const existingParams = new URLSearchParams(currentUrl.search);
        
        // Also include form values that might not be in URL yet
        if (filterForm.warehouse_id) existingParams.set('warehouse_id', filterForm.warehouse_id.toString());
        if (filterForm.product_id) existingParams.set('product_id', filterForm.product_id.toString());
        if (filterForm.type) existingParams.set('type', filterForm.type.toString());
        if (filterForm.supplier_id) existingParams.set('supplier_id', filterForm.supplier_id.toString());
        if (filterForm.factor_number) existingParams.set('factor_number', filterForm.factor_number.toString());
        if (filterForm.date_from) existingParams.set('date_from', filterForm.date_from.toString());
        if (filterForm.date_to) existingParams.set('date_to', filterForm.date_to.toString());

        const url = `/warehouse/stock-movements/export/excel?${existingParams.toString()}`;
        window.location.href = url;
    } finally {
        isExporting.value = false;
    }
};

const exportToPdf = async () => {
    isExporting.value = true;
    try {
        // Get current URL query string (which contains applied filters)
        const currentUrl = new URL(window.location.href);
        const existingParams = new URLSearchParams(currentUrl.search);
        
        // Also include form values that might not be in URL yet
        if (filterForm.warehouse_id) existingParams.set('warehouse_id', filterForm.warehouse_id.toString());
        if (filterForm.product_id) existingParams.set('product_id', filterForm.product_id.toString());
        if (filterForm.type) existingParams.set('type', filterForm.type.toString());
        if (filterForm.supplier_id) existingParams.set('supplier_id', filterForm.supplier_id.toString());
        if (filterForm.factor_number) existingParams.set('factor_number', filterForm.factor_number.toString());
        if (filterForm.date_from) existingParams.set('date_from', filterForm.date_from.toString());
        if (filterForm.date_to) existingParams.set('date_to', filterForm.date_to.toString());

        const url = `/warehouse/stock-movements/export/pdf?${existingParams.toString()}`;
        window.location.href = url;
    } finally {
        isExporting.value = false;
    }
};
</script>

<template>
    <Head :title="t('stockMovements.title')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="flex flex-row items-center justify-between gap-4 p-4 md:p-6 pb-0">
                    <div>
                        <h1 class="text-xl font-semibold">{{ t('nav.movements') }}</h1>
                        <p class="text-sm text-muted-foreground">{{ t('stockMovements.title') }}</p>
                    </div>
                    <Link v-if="can('stock.in')" :href="create.url()"><Button><Plus class="mr-2 h-4 w-4" />{{ t('nav.input') }}</Button></Link>
                </div>
            </template>
            <div class="p-4 md:p-6 pt-4 overflow-y-auto">
                <!-- Filters Section - Collapsible -->
                <div class="bg-card border border-border rounded-lg mb-6">
                    <!-- Filter Header -->
                    <div class="flex items-center justify-between p-4 cursor-pointer hover:bg-muted/50 transition-colors" @click="toggleFilters">
                        <h3 class="text-sm font-semibold">{{ t('common.filters') || 'Filters' }}</h3>
                        <ChevronDown class="h-5 w-5 transition-transform" :class="{ 'rotate-180': showFilters }" />
                    </div>

                    <!-- Filter Content -->
                    <div v-show="showFilters" class="border-t border-border p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                            <!-- Warehouse Filter -->
                        <div>
                            <label class="text-xs font-medium text-muted-foreground mb-2 block">{{ t('stock.warehouse') }}</label>
                            <select v-model="filterForm.warehouse_id" class="w-full px-3 py-2 border border-border rounded-md bg-background text-sm">
                                <option value="">{{ t('common.selectAll') || 'Select All' }}</option>
                                <option v-for="warehouse in warehouses" :key="(warehouse as any).id" :value="(warehouse as any).id.toString()">
                                    {{ (warehouse as any)[locale] }}
                                </option>
                            </select>
                        </div>

                        <!-- Supplier Filter -->
                        <div>
                            <label class="text-xs font-medium text-muted-foreground mb-2 block">{{ t('stock.supplier') || 'Supplier' }}</label>
                            <select v-model="filterForm.supplier_id" class="w-full px-3 py-2 border border-border rounded-md bg-background text-sm">
                                <option value="">{{ t('common.selectAll') || 'Select All' }}</option>
                                <option v-for="supplier in suppliers" :key="(supplier as any).id" :value="(supplier as any).id.toString()">
                                    {{ (supplier as any).name }}
                                </option>
                            </select>
                        </div>

                        <!-- Factor Number Filter -->
                        <div>
                            <label class="text-xs font-medium text-muted-foreground mb-2 block">{{ t('stock.factorNumber') || 'Invoice No' }}</label>
                            <Input
                                v-model="filterForm.factor_number"
                                type="text"
                                :placeholder="t('common.search') || 'Search...'"
                                class="w-full"
                            />
                        </div>

                        <!-- Type Filter -->
                        <div>
                            <label class="text-xs font-medium text-muted-foreground mb-2 block">{{ t('stockMovements.type') }}</label>
                            <select v-model="filterForm.type" class="w-full px-3 py-2 border border-border rounded-md bg-background text-sm">
                                <option value="">{{ t('common.selectAll') || 'Select All' }}</option>
                                <option value="in">{{ t('nav.input') }}</option>
                                <option value="out">{{ t('nav.output') }}</option>
                                <option value="transfer">{{ t('common.transfer') || 'Transfer' }}</option>
                                <option value="adjustment">{{ t('common.adjustment') || 'Adjustment' }}</option>
                            </select>
                        </div>

                        <!-- Date From -->
                        <div>
                            <label class="text-xs font-medium text-muted-foreground mb-2 block">{{ t('common.dateFrom') || 'From Date' }}</label>
                            <Input
                                v-model="filterForm.date_from"
                                type="date"
                                class="w-full"
                            />
                        </div>

                        <!-- Date To -->
                        <div>
                            <label class="text-xs font-medium text-muted-foreground mb-2 block">{{ t('common.dateTo') || 'To Date' }}</label>
                            <Input
                                v-model="filterForm.date_to"
                                type="date"
                                class="w-full"
                            />
                        </div>
                    </div>

                    <!-- Filter Actions -->
                    <div class="flex gap-2 justify-end">
                        <Button variant="outline" @click="resetFilters" :disabled="filterForm.processing">
                            {{ t('common.reset') || 'Reset' }}
                        </Button>
                        <Button @click="applyFilters" :disabled="filterForm.processing">
                            {{ t('common.applyFilters') || 'Apply Filters' }}
                        </Button>
                        </div>
                    </div>
                </div>

                <!-- Export Section -->
                <div class="bg-card border border-border rounded-lg p-4 mb-6">
                    <h3 class="text-sm font-semibold mb-4">{{ t('common.export') || 'Export' }}</h3>
                    <div class="flex items-center gap-4 flex-wrap">
                        <Button 
                            @click="exportToExcel" 
                            :disabled="isExporting"
                            variant="outline"
                            class="gap-2"
                        >
                            <Download class="h-4 w-4" />
                            {{ t('common.exportExcel') || 'Export to Excel' }}
                        </Button>
                        <Button 
                            @click="exportToPdf" 
                            :disabled="isExporting"
                            variant="outline"
                            class="gap-2"
                        >
                            <FileText class="h-4 w-4" />
                            {{ t('common.exportPdf') || 'Export to PDF' }}
                        </Button>
                        
                        <!-- Export Type Info -->
                        <div class="text-xs text-muted-foreground ml-auto">
                            {{ t('common.exporting') || 'Exporting' }}: 
                            <strong>{{ t('common.filteredData') || 'Filtered Data' }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Table Section -->
                <Table class="bg-transparent">
                    <TableHeader>
                        <TableRow class="border-b border-border">
                            <TableHead class="text-muted-foreground">{{ t('common.date') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('stock.product') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('stockMovements.type') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('common.quantity') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('stock.warehouse') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('stock.supplier') || 'Supplier' }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('stock.factorNumber') || 'Invoice No' }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('activityLogs.user') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('common.actions') }}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="m in movements.data" :key="(m as any).id" class="border-b border-border hover:bg-muted/30">
                            <TableCell class="whitespace-nowrap">{{ formatTurkeyDate((m as any).movement_date) }}</TableCell>
                            <TableCell>{{ (m as any).product?.[locale] ?? '-' }}</TableCell>
                            <TableCell>
                                <Badge
                                    :variant="(m as any).type === 'in' ? 'success' : (m as any).type === 'out' ? 'destructive' : 'secondary'"
                                >
                                    {{ (m as any).type === 'in' ? t('nav.input') : (m as any).type === 'out' ? t('nav.output') : (m as any).type }}
                                </Badge>
                            </TableCell>
                            <TableCell>{{ (m as any).quantity }}</TableCell>
                            <TableCell>{{ (m as any).warehouse?.[locale] ?? '-' }}</TableCell>
                            <TableCell>{{ (m as any).supplier?.name ?? '-' }}</TableCell>
                            <TableCell>{{ (m as any).factor_number ?? '-' }}</TableCell>
                            <TableCell>{{ (m as any).user?.name ?? '-' }}</TableCell>
                            <TableCell class="flex gap-2">
                                <Link v-if="can('stock_movements.edit')" :href="`/warehouse/stock-movements/${(m as any).id}/edit`">
                                    <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                </Link>
                                <Button v-if="can('stock_movements.delete')" variant="ghost" size="sm" class="h-8 w-8 p-0 text-destructive hover:text-destructive" @click="confirmDelete((m as any).id)">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Delete Confirmation Dialog -->
                <div v-if="deleteMovementId !== null" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div class="bg-background rounded-lg shadow-lg p-6 max-w-sm mx-auto">
                        <h2 class="text-lg font-semibold mb-2">{{ t('common.confirmDelete') }}</h2>
                        <p class="text-sm text-muted-foreground mb-6">{{ t('common.deleteWarning') }}</p>
                        <div class="flex gap-3 justify-end">
                            <Button variant="outline" @click="cancelDelete">{{ t('common.cancel') }}</Button>
                            <Button variant="destructive" @click="deleteMovement(deleteMovementId)" :disabled="form.processing">
                                {{ t('common.delete') }}
                            </Button>
                        </div>
                    </div>
                </div>
                <Pagination v-if="movements.links?.length" :links="movements.links" class="mt-4" />
            </div>
        </AppPageContent>
    </AppLayout>
</template>
