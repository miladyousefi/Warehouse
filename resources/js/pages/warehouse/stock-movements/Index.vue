<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Plus,
    Edit,
    Trash2,
    Download,
    FileText,
    Printer,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import {
    create,
} from '@/actions/App/Http/Controllers/Warehouse/StockMovementController';
import { index } from '@/routes/warehouse/stock-movements';
import AppPageContent from '@/components/AppPageContent.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { usePermission } from '@/composables/usePermission';
import { formatTurkeyDate } from '@/composables/useTurkeyDate';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

defineProps<{
    movements: {
        data: Array<Record<string, unknown>>;
        links: Array<{ url: string | null; label: string }>;
    };
    warehouses: Array<Record<string, unknown>>;
    suppliers: Array<Record<string, unknown>>;
}>();

const { t } = useI18n();
const { can } = usePermission();
const locale = computed(() =>
    useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en',
);
const breadcrumbs: BreadcrumbItem[] = [
    { title: t('nav.stockMovements'), href: index.url() },
];

const inlineSearchClass =
    'h-10 w-full rounded-none border-0 border-b border-slate-300/70 bg-transparent pl-2.5 pr-0 shadow-none focus-visible:ring-0 focus-visible:ring-offset-0 focus-visible:border-slate-500 dark:border-white/20 dark:focus-visible:border-white/40';

// Form state
const deleteMovementId = ref<number | null>(null);
const form = useForm({});
const filtersOpen = ref(false);
const exportOpen = ref(false);
const queryParams = new URLSearchParams(window.location.search);
const filterForm = useForm({
    search: queryParams.get('search') ?? '',
    warehouse_id: queryParams.get('warehouse_id') ?? '',
    product_id: queryParams.get('product_id') ?? '',
    type: queryParams.get('type') ?? '',
    supplier_id: queryParams.get('supplier_id') ?? '',
    date_from: queryParams.get('date_from') ?? '',
    date_to: queryParams.get('date_to') ?? '',
});

const isExporting = ref(false);

const openFilters = () => {
    filtersOpen.value = true;
};

const openExport = () => {
    exportOpen.value = true;
};

const filterFieldClass =
    'h-11 rounded-xl border-amber-200/70 bg-white/70 shadow-sm backdrop-blur focus-visible:border-amber-400 focus-visible:ring-amber-400/25 dark:border-amber-200/30 dark:bg-white/10';

const filterSelectClass =
    'h-11 w-full rounded-xl border border-amber-200/70 bg-white/70 px-3 text-sm shadow-sm backdrop-blur focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/25 dark:border-amber-200/30 dark:bg-white/10';

const movementTypeLabel = (type: string) => {
    if (type === 'in') return t('nav.input');
    if (type === 'out') return t('nav.output');
    if (type === 'transfer') return t('common.transfer') || 'Transfer';
    if (type === 'adjustment') return t('common.adjustment') || 'Adjustment';
    return type;
};

const movementTypeTextClass = (type: string) => {
    if (type === 'in') return 'text-emerald-700 dark:text-emerald-400';
    if (type === 'out') return 'text-rose-700 dark:text-rose-400';
    if (type === 'transfer') return 'text-amber-800 dark:text-amber-300';
    if (type === 'adjustment') return 'text-slate-700 dark:text-slate-300';
    return 'text-muted-foreground';
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

let factorDebounce: number | null = null;
watch(
    () => filterForm.search,
    () => {
        if (factorDebounce) window.clearTimeout(factorDebounce);
        factorDebounce = window.setTimeout(() => {
            applyFilters();
        }, 350);
    },
);

const resetFilters = () => {
    filterForm.reset();
    filterForm.get(route('warehouse.stock-movements.index'), {
        only: ['movements', 'suppliers', 'warehouses'],
    });
};

const applyFiltersFromModal = () => {
    applyFilters();
    filtersOpen.value = false;
};

const resetFiltersFromModal = () => {
    resetFilters();
    filtersOpen.value = false;
};

const exportToExcel = async () => {
    isExporting.value = true;
    try {
        // Get current URL query string (which contains applied filters)
        const currentUrl = new URL(window.location.href);
        const existingParams = new URLSearchParams(currentUrl.search);

        // Also include form values that might not be in URL yet
        if (filterForm.search)
            existingParams.set('search', filterForm.search.toString());
        if (filterForm.warehouse_id)
            existingParams.set(
                'warehouse_id',
                filterForm.warehouse_id.toString(),
            );
        if (filterForm.product_id)
            existingParams.set('product_id', filterForm.product_id.toString());
        if (filterForm.type)
            existingParams.set('type', filterForm.type.toString());
        if (filterForm.supplier_id)
            existingParams.set(
                'supplier_id',
                filterForm.supplier_id.toString(),
            );
        if (filterForm.date_from)
            existingParams.set('date_from', filterForm.date_from.toString());
        if (filterForm.date_to)
            existingParams.set('date_to', filterForm.date_to.toString());

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
        if (filterForm.search)
            existingParams.set('search', filterForm.search.toString());
        if (filterForm.warehouse_id)
            existingParams.set(
                'warehouse_id',
                filterForm.warehouse_id.toString(),
            );
        if (filterForm.product_id)
            existingParams.set('product_id', filterForm.product_id.toString());
        if (filterForm.type)
            existingParams.set('type', filterForm.type.toString());
        if (filterForm.supplier_id)
            existingParams.set(
                'supplier_id',
                filterForm.supplier_id.toString(),
            );
        if (filterForm.date_from)
            existingParams.set('date_from', filterForm.date_from.toString());
        if (filterForm.date_to)
            existingParams.set('date_to', filterForm.date_to.toString());

        const url = `/warehouse/stock-movements/export/pdf?${existingParams.toString()}`;
        window.location.href = url;
    } finally {
        isExporting.value = false;
    }
};

const printPdf = () => {
    const currentUrl = new URL(window.location.href);
    const existingParams = new URLSearchParams(currentUrl.search);

    if (filterForm.search)
        existingParams.set('search', filterForm.search.toString());
    if (filterForm.warehouse_id)
        existingParams.set('warehouse_id', filterForm.warehouse_id.toString());
    if (filterForm.product_id)
        existingParams.set('product_id', filterForm.product_id.toString());
    if (filterForm.type) existingParams.set('type', filterForm.type.toString());
    if (filterForm.supplier_id)
        existingParams.set('supplier_id', filterForm.supplier_id.toString());
    if (filterForm.date_from)
        existingParams.set('date_from', filterForm.date_from.toString());
    if (filterForm.date_to)
        existingParams.set('date_to', filterForm.date_to.toString());
    existingParams.set('print', '1');

    window.open(
        `/warehouse/stock-movements/export/pdf?${existingParams.toString()}`,
        '_blank',
    );
};

const exportExcelFromModal = async () => {
    await exportToExcel();
    exportOpen.value = false;
};

const exportPdfFromModal = async () => {
    await exportToPdf();
    exportOpen.value = false;
};

const printPdfFromModal = () => {
    printPdf();
    exportOpen.value = false;
};
</script>

<template>
    <Head :title="t('stockMovements.title')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="flex flex-col gap-3 p-4 pb-0 md:p-6">
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <h1 class="text-xl font-semibold">
                                {{ t('nav.movements') }}
                            </h1>
                            <p class="text-sm text-muted-foreground">
                                {{ t('stockMovements.title') }}
                            </p>
                        </div>
                        <div
                            class="flex flex-wrap items-center justify-start gap-2 sm:justify-end"
                        >
                            <Button
                                size="sm"
                                variant="outline"
                                @click="openFilters"
                            >
                                {{ t('common.filters') || 'Filters' }}
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                class="gap-2"
                                @click="openExport"
                            >
                                <Download class="h-4 w-4" />
                                <span class="hidden sm:inline">{{
                                    t('common.export') || 'Export'
                                }}</span>
                            </Button>
                            <Link v-if="can('stock.in')" :href="create.url()">
                                <Button size="sm">
                                    <Plus class="mr-2 h-4 w-4" />{{
                                        t('nav.input')
                                    }}
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </template>
            <div class="p-4 pt-2 md:p-6 md:pt-3">
                <Dialog v-model:open="filtersOpen">
                    <DialogContent class="sm:max-w-3xl">
                        <DialogHeader>
                            <DialogTitle>
                                {{ t('common.filters') || 'Filters' }}
                            </DialogTitle>
                            <DialogDescription>
                                {{
                                    locale === 'tr'
                                        ? 'Hareketleri depoya, tedarikçiye ve tarihe göre filtreleyin.'
                                        : 'Filter movements by warehouse, supplier, and date.'
                                }}
                            </DialogDescription>
                        </DialogHeader>

                        <div class="max-h-[70vh] overflow-y-auto pr-1">
                            <div
                                class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4"
                            >
                                <div>
                                    <Label
                                        class="mb-2 block text-xs font-medium text-muted-foreground"
                                        >{{ t('stock.warehouse') }}</Label
                                    >
                                    <select
                                        v-model="filterForm.warehouse_id"
                                        :class="filterSelectClass"
                                    >
                                        <option value="">
                                            {{
                                                t('common.selectAll') ||
                                                'Select All'
                                            }}
                                        </option>
                                        <option
                                            v-for="warehouse in warehouses"
                                            :key="(warehouse as any).id"
                                            :value="
                                                (warehouse as any).id.toString()
                                            "
                                        >
                                            {{ (warehouse as any)[locale] }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <Label
                                        class="mb-2 block text-xs font-medium text-muted-foreground"
                                        >{{
                                            t('stock.supplier') || 'Supplier'
                                        }}</Label
                                    >
                                    <select
                                        v-model="filterForm.supplier_id"
                                        :class="filterSelectClass"
                                    >
                                        <option value="">
                                            {{
                                                t('common.selectAll') ||
                                                'Select All'
                                            }}
                                        </option>
                                        <option
                                            v-for="supplier in suppliers"
                                            :key="(supplier as any).id"
                                            :value="
                                                (supplier as any).id.toString()
                                            "
                                        >
                                            {{ (supplier as any).name }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <Label
                                        class="mb-2 block text-xs font-medium text-muted-foreground"
                                        >{{ t('stockMovements.type') }}</Label
                                    >
                                    <select
                                        v-model="filterForm.type"
                                        :class="filterSelectClass"
                                    >
                                        <option value="">
                                            {{
                                                t('common.selectAll') ||
                                                'Select All'
                                            }}
                                        </option>
                                        <option value="in">
                                            {{ t('nav.input') }}
                                        </option>
                                        <option value="out">
                                            {{ t('nav.output') }}
                                        </option>
                                        <option value="transfer">
                                            {{
                                                t('common.transfer') ||
                                                'Transfer'
                                            }}
                                        </option>
                                        <option value="adjustment">
                                            {{
                                                t('common.adjustment') ||
                                                'Adjustment'
                                            }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <Label
                                        class="mb-2 block text-xs font-medium text-muted-foreground"
                                        >{{
                                            t('common.dateFrom') || 'From Date'
                                        }}</Label
                                    >
                                    <Input
                                        v-model="filterForm.date_from"
                                        type="date"
                                        class="w-full"
                                        :class="filterFieldClass"
                                    />
                                </div>

                                <div>
                                    <Label
                                        class="mb-2 block text-xs font-medium text-muted-foreground"
                                        >{{
                                            t('common.dateTo') || 'To Date'
                                        }}</Label
                                    >
                                    <Input
                                        v-model="filterForm.date_to"
                                        type="date"
                                        class="w-full"
                                        :class="filterFieldClass"
                                    />
                                </div>
                            </div>
                        </div>

                        <DialogFooter
                            class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end sm:gap-2"
                        >
                            <Button
                                variant="outline"
                                @click="resetFiltersFromModal"
                                :disabled="filterForm.processing"
                            >
                                {{ t('common.reset') || 'Reset' }}
                            </Button>
                            <Button
                                variant="outline"
                                @click="() => (filtersOpen = false)"
                            >
                                {{ t('common.close') || 'Close' }}
                            </Button>
                            <Button
                                @click="applyFiltersFromModal"
                                :disabled="filterForm.processing"
                            >
                                {{
                                    t('common.applyFilters') || 'Apply Filters'
                                }}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <Dialog v-model:open="exportOpen">
                    <DialogContent class="sm:max-w-xl">
                        <DialogHeader>
                            <DialogTitle>
                                {{ t('common.export') || 'Export' }}
                            </DialogTitle>
                            <DialogDescription>
                                {{
                                    locale === 'tr'
                                        ? 'Filtrelenmiş veriyi Excel/PDF olarak dışa aktarın.'
                                        : 'Export filtered data to Excel/PDF.'
                                }}
                            </DialogDescription>
                        </DialogHeader>

                        <div class="flex flex-wrap items-center gap-3">
                            <Button
                                @click="exportExcelFromModal"
                                :disabled="isExporting"
                                variant="outline"
                                class="gap-2"
                            >
                                <Download class="h-4 w-4" />
                                {{
                                    t('common.exportExcel') || 'Export to Excel'
                                }}
                            </Button>
                            <Button
                                @click="exportPdfFromModal"
                                :disabled="isExporting"
                                variant="outline"
                                class="gap-2"
                            >
                                <FileText class="h-4 w-4" />
                                {{ t('common.exportPdf') || 'Export to PDF' }}
                            </Button>
                            <Button
                                @click="printPdfFromModal"
                                variant="outline"
                                class="gap-2"
                            >
                                <Printer class="h-4 w-4" />
                                {{ t('common.print') || 'Print PDF' }}
                            </Button>

                            <div class="ml-auto text-xs text-muted-foreground">
                                {{ t('common.exporting') || 'Exporting' }}:
                                <strong>{{
                                    t('common.filteredData') || 'Filtered Data'
                                }}</strong>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button
                                variant="outline"
                                @click="() => (exportOpen = false)"
                            >
                                {{ t('common.close') || 'Close' }}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <div
                    class="sticky top-0 z-10 -mx-4 mb-1 bg-transparent px-4 py-1 backdrop-blur supports-[backdrop-filter]:bg-background/5 md:-mx-6 md:px-6"
                >
                    <div class="w-full sm:max-w-[260px] md:max-w-[320px]">
                        <Input
                            v-model="filterForm.search"
                            type="text"
                            :placeholder="t('common.search') || 'Search...'"
                            :class="inlineSearchClass"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                </div>

                <!-- Table Section -->
                <Table>
                    <TableHeader>
                        <TableRow class="border-b border-border">
                            <TableHead class="text-muted-foreground">{{
                                t('common.date')
                            }}</TableHead>
                            <TableHead class="text-muted-foreground">{{
                                t('stock.product')
                            }}</TableHead>
                            <TableHead class="text-muted-foreground">{{
                                t('stockMovements.type')
                            }}</TableHead>
                            <TableHead class="text-muted-foreground">{{
                                t('common.quantity')
                            }}</TableHead>
                            <TableHead class="text-muted-foreground">{{
                                t('stock.warehouse')
                            }}</TableHead>
                            <TableHead
                                class="hidden text-muted-foreground md:table-cell"
                            >
                                {{
                                    t('stock.supplier') || 'Supplier'
                                }}</TableHead
                            >
                            <TableHead
                                class="hidden text-muted-foreground lg:table-cell"
                            >
                                {{
                                    t('stock.factorNumber') || 'Invoice No'
                                }}</TableHead
                            >
                            <TableHead
                                class="hidden text-muted-foreground xl:table-cell"
                            >
                                {{ t('activityLogs.user') }}</TableHead
                            >
                            <TableHead class="text-muted-foreground">{{
                                t('common.actions')
                            }}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="m in movements.data"
                            :key="(m as any).id"
                            class="border-b border-border hover:bg-muted/30"
                        >
                            <TableCell class="whitespace-nowrap">{{
                                formatTurkeyDate((m as any).movement_date)
                            }}</TableCell>
                            <TableCell>{{
                                (m as any).product?.[locale] ?? '-'
                            }}</TableCell>
                            <TableCell>
                                <span
                                    class="text-sm font-semibold"
                                    :class="
                                        movementTypeTextClass((m as any).type)
                                    "
                                >
                                    {{ movementTypeLabel((m as any).type) }}
                                </span>
                            </TableCell>
                            <TableCell>{{ (m as any).quantity }}</TableCell>
                            <TableCell>{{
                                (m as any).warehouse?.[locale] ?? '-'
                            }}</TableCell>
                            <TableCell class="hidden md:table-cell">{{
                                (m as any).supplier?.name ?? '-'
                            }}</TableCell>
                            <TableCell class="hidden lg:table-cell">{{
                                (m as any).factor_number ?? '-'
                            }}</TableCell>
                            <TableCell class="hidden xl:table-cell">{{
                                (m as any).user?.name ?? '-'
                            }}</TableCell>
                            <TableCell class="whitespace-nowrap">
                                <div class="flex gap-2">
                                    <Link
                                        v-if="can('stock_movements.edit')"
                                        :href="`/warehouse/stock-movements/${(m as any).id}/edit`"
                                    >
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-8 w-8 p-0"
                                        >
                                            <Edit class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="can('stock_movements.delete')"
                                        variant="ghost"
                                        size="sm"
                                        class="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                        @click="confirmDelete((m as any).id)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Delete Confirmation Dialog -->
                <div
                    v-if="deleteMovementId !== null"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                >
                    <div
                        class="mx-auto max-w-sm rounded-lg bg-background p-6 shadow-lg"
                    >
                        <h2 class="mb-2 text-lg font-semibold">
                            {{ t('common.confirmDelete') }}
                        </h2>
                        <p class="mb-6 text-sm text-muted-foreground">
                            {{ t('common.deleteWarning') }}
                        </p>
                        <div class="flex justify-end gap-3">
                            <Button variant="outline" @click="cancelDelete">{{
                                t('common.cancel')
                            }}</Button>
                            <Button
                                variant="destructive"
                                @click="deleteMovement(deleteMovementId)"
                                :disabled="form.processing"
                            >
                                {{ t('common.delete') }}
                            </Button>
                        </div>
                    </div>
                </div>
                <Pagination
                    v-if="movements.links?.length"
                    :links="movements.links"
                    class="mt-4"
                />
            </div>
        </AppPageContent>
    </AppLayout>
</template>
