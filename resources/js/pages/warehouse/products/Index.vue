<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2, Eye, MoreHorizontal, Download, FileText, ChevronDown, Check, ArrowRightLeft, Printer } from 'lucide-vue-next';
import { ref, computed, watch, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { index, create } from '@/actions/App/Http/Controllers/Warehouse/ProductController';
import { store as stockMovementStore } from '@/actions/App/Http/Controllers/Warehouse/StockMovementController';
import AppPageContent from '@/components/AppPageContent.vue';
import Pagination from '@/components/Pagination.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { usePermission } from '@/composables/usePermission';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Props {
    products: { data: Array<Record<string, unknown>>; links: Array<{ url: string | null; label: string }> };
    categories: Array<Record<string, unknown>>;
    warehouses: Array<Record<string, unknown>>;
}

const props = defineProps<Props>();
const { t } = useI18n();
const { can } = usePermission();
const queryParams = new URLSearchParams(window.location.search);
const search = ref(queryParams.get('search') ?? '');
const categoryId = ref(queryParams.get('category_id') ?? '');
const warehouseId = ref(queryParams.get('warehouse_id') ?? '');
const isActive = ref(queryParams.get('is_active') ?? '');
const movementDateFrom = ref(queryParams.get('movement_date_from') ?? '');
const movementDateTo = ref(queryParams.get('movement_date_to') ?? '');
const locale = computed(() => (useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en'));
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.products'), href: index.url() }];
const hasMovementDateFilter = computed(() => Boolean(movementDateFrom.value || movementDateTo.value));

// Selection state - use localStorage for persistence across page navigation
const selectedProducts = ref<Set<number>>(new Set());
const storageKey = 'products_selected_ids';

// Initialize selected products from localStorage
onMounted(() => {
    const stored = localStorage.getItem(storageKey);
    if (stored) {
        try {
            const ids = JSON.parse(stored);
            selectedProducts.value = new Set(ids);
        } catch (e) {
            console.error('Failed to parse selected products from localStorage:', e);
        }
    }
});

// Watch for changes to selectedProducts and persist to localStorage
watch(selectedProducts, (newSelection) => {
    const ids = Array.from(newSelection);
    localStorage.setItem(storageKey, JSON.stringify(ids));
}, { deep: true });

const showFilters = ref(false);
const isExporting = ref(false);
const form = useForm({});
const transferModalOpen = ref(false);
const transferProduct = ref<Record<string, any> | null>(null);
const transferForm = useForm({
    type: 'transfer',
    from_warehouse_id: '',
    rows: [
        {
            product_id: '',
            warehouse_id: '',
            from_warehouse_id: '',
        },
    ],
});

const categoryOptions = computed(() => [
    { id: '', label: t('common.all') || 'All' },
    ...props.categories.map((c: any) => ({
        id: c.id,
        label: c[locale.value] || c.name_tr || c.name_en,
    }))
]);

const toggleFilters = () => {
    showFilters.value = !showFilters.value;
};

const toggleAllSelection = () => {
    if (selectedProducts.value.size === props.products.data.length) {
        selectedProducts.value.clear();
    } else {
        props.products.data.forEach((p: any) => {
            selectedProducts.value.add(p.id);
        });
    }
};

const toggleProductSelection = (id: number) => {
    if (selectedProducts.value.has(id)) {
        selectedProducts.value.delete(id);
    } else {
        selectedProducts.value.add(id);
    }
};

const isAllSelected = computed(() => {
    return props.products.data.length > 0 && selectedProducts.value.size === props.products.data.length;
});

const isIndeterminate = computed(() => {
    return selectedProducts.value.size > 0 && selectedProducts.value.size < props.products.data.length;
});

const isProductSelected = (id: number) => {
    return selectedProducts.value.has(id);
};

const warehouseOptions = computed(() =>
    props.warehouses.map((w: any) => ({
        id: String(w.id),
        label: w[locale.value] || w.name_tr || w.name_en || `#${w.id}`,
    }))
);

const warehouseNameById = (id: string | number | null | undefined) => {
    if (!id) return '-';
    const found = props.warehouses.find((w: any) => String(w.id) === String(id));
    return found ? (found as any)[locale.value] || (found as any).name_tr || (found as any).name_en || '-' : '-';
};

function openTransferModal(product: Record<string, any>) {
    const balances = ((product as any).stockBalances || []) as Array<Record<string, any>>;
    const fromByCurrentFilter = warehouseId.value
        ? balances.find((b: any) => String(b.warehouse_id) === String(warehouseId.value))
        : null;
    const fromPositive = balances.find((b: any) => Number(b.quantity) > 0);
    const defaultFromWarehouse = String(
        fromByCurrentFilter?.warehouse_id
        ?? fromPositive?.warehouse_id
        ?? balances[0]?.warehouse_id
        ?? (product as any).warehouse_id
        ?? warehouseId.value
        ?? ''
    );

    transferProduct.value = product;
    transferForm.reset();
    transferForm.clearErrors();
    transferForm.type = 'transfer';
    transferForm.rows = [
        {
            product_id: String((product as any).id),
            warehouse_id: '',
            from_warehouse_id: defaultFromWarehouse,
        },
    ];
    transferForm.from_warehouse_id = defaultFromWarehouse;
    transferModalOpen.value = true;
}

function buildFilterParams() {
    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    if (categoryId.value) params.set('category_id', categoryId.value);
    if (isActive.value) params.set('is_active', isActive.value);
    if (warehouseId.value) params.set('warehouse_id', warehouseId.value);
    if (movementDateFrom.value) params.set('movement_date_from', movementDateFrom.value);
    if (movementDateTo.value) params.set('movement_date_to', movementDateTo.value);
    return params;
}

function submitTransfer() {
    transferForm.rows[0].from_warehouse_id = transferForm.from_warehouse_id;
    transferForm.post(stockMovementStore.url(), {
        preserveScroll: true,
        onSuccess: () => {
            transferModalOpen.value = false;
            transferProduct.value = null;
            transferForm.reset();
        },
    });
}

function doSearch() {
    router.get(index.url(), 
        { 
            search: search.value || undefined, 
            category_id: categoryId.value || undefined,
            is_active: isActive.value || undefined,
            warehouse_id: warehouseId.value || undefined,
            movement_date_from: movementDateFrom.value || undefined,
            movement_date_to: movementDateTo.value || undefined,
        }, 
        { preserveState: false }
    );
}

function resetFilters() {
    search.value = '';
    categoryId.value = '';
    warehouseId.value = '';
    isActive.value = '';
    movementDateFrom.value = '';
    movementDateTo.value = '';
    router.get(index.url());
}

const clearSelections = () => {
    selectedProducts.value.clear();
    localStorage.removeItem(storageKey);
};

const exportToExcel = async () => {
    isExporting.value = true;
    try {
        if (selectedProducts.value.size > 0) {
            // If products are selected, export only selected ones
            const form = new FormData();
            selectedProducts.value.forEach(id => form.append('product_ids[]', id));
            
            const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;
            if (!csrfToken) {
                console.error('CSRF token not found in meta tag');
                alert('Error: Security token not found. Please refresh the page and try again.');
                return;
            }

            const response = await fetch('/warehouse/products/export/excel', {
                method: 'POST',
                body: form,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                }
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `products-${new Date().getTime()}.xlsx`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);
            } else {
                console.error(`Export failed with status ${response.status}:`, response.statusText);
                alert(`Export failed: ${response.statusText}`);
            }
        } else {
            // Export all filtered products
            const url = `/warehouse/products/export/excel?${buildFilterParams().toString()}`;
            window.location.href = url;
        }
    } catch (error) {
        console.error('Export error:', error);
        alert('An error occurred during export. Please check the console for details.');
    } finally {
        isExporting.value = false;
    }
};

const exportToPdf = async () => {
    isExporting.value = true;
    try {
        if (selectedProducts.value.size > 0) {
            // If products are selected, export only selected ones
            const form = new FormData();
            selectedProducts.value.forEach(id => form.append('product_ids[]', id));
            
            const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;
            if (!csrfToken) {
                console.error('CSRF token not found in meta tag');
                alert('Error: Security token not found. Please refresh the page and try again.');
                return;
            }

            const response = await fetch('/warehouse/products/export/pdf', {
                method: 'POST',
                body: form,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                }
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `products-${new Date().getTime()}.pdf`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);
            } else {
                console.error(`Export failed with status ${response.status}:`, response.statusText);
                alert(`Export failed: ${response.statusText}`);
            }
        } else {
            // Export all filtered products
            const url = `/warehouse/products/export/pdf?${buildFilterParams().toString()}`;
            window.location.href = url;
        }
    } catch (error) {
        console.error('Export error:', error);
        alert('An error occurred during export. Please check the console for details.');
    } finally {
        isExporting.value = false;
    }
};

const bulkDelete = async () => {
    if (selectedProducts.value.size === 0) {
        alert(t('common.selectItems') || 'Please select items to delete');
        return;
    }

    if (!confirm(t('common.confirmDelete') || `Delete ${selectedProducts.value.size} item(s)?`)) {
        return;
    }

    form.post(route('warehouse.products.bulk-delete'), {
        data: {
            product_ids: Array.from(selectedProducts.value),
        },
        onSuccess: () => {
            selectedProducts.value.clear();
            localStorage.removeItem(storageKey);
            router.reload();
        },
        onError: (errors) => {
            console.error('Error:', errors);
        },
    });
};

function destroy(id: number): void {
    if (confirm(t('common.delete') + '?')) router.delete(`/warehouse/products/${id}`);
}

function printPdfExport() {
    if (selectedProducts.value.size > 0) {
        const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;
        if (!csrfToken) {
            alert('Error: Security token not found. Please refresh the page and try again.');
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/warehouse/products/export/pdf';
        form.target = '_blank';

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);

        const printInput = document.createElement('input');
        printInput.type = 'hidden';
        printInput.name = 'print';
        printInput.value = '1';
        form.appendChild(printInput);

        selectedProducts.value.forEach((id) => {
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'product_ids[]';
            idInput.value = String(id);
            form.appendChild(idInput);
        });

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
        return;
    }

    const params = buildFilterParams();
    params.set('print', '1');
    window.open(`/warehouse/products/export/pdf?${params.toString()}`, '_blank');
}
</script>

<template>
    <Head :title="t('products.title')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="flex flex-col gap-4 p-4 md:p-6 pb-0">
                    <div class="flex flex-row items-center justify-between">
                        <div>
                            <h1 class="text-xl font-semibold">{{ t('products.title') }}</h1>
                            <p class="text-sm text-muted-foreground">{{ t('common.view') }}</p>
                        </div>
                        <Link v-if="can('products.create')" :href="create.url()">
                            <Button><Plus class="mr-2 h-4 w-4" />{{ t('products.createProduct') }}</Button>
                        </Link>
                    </div>
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
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-4">
                            <!-- Search Filter -->
                            <div>
                                <label class="text-xs font-medium text-muted-foreground mb-2 block">{{ t('common.search') }}</label>
                                <Input v-model="search" :placeholder="t('common.search') || 'Search...'" class="w-full" />
                            </div>

                            <!-- Category Filter -->
                            <div>
                                <label class="text-xs font-medium text-muted-foreground mb-2 block">{{ t('common.category') }}</label>
                                <select v-model="categoryId" class="w-full px-3 py-2 border border-border rounded-md bg-background text-sm">
                                    <option value="">{{ t('common.selectAll') || 'Select All' }}</option>
                                    <option v-for="category in categories" :key="(category as any).id" :value="(category as any).id.toString()">
                                        {{ (category as any)[locale] }}
                                    </option>
                                </select>
                            </div>

                            <!-- Status Filter -->
                            <div>
                                <label class="text-xs font-medium text-muted-foreground mb-2 block">{{ t('common.status') }}</label>
                                <select v-model="isActive" class="w-full px-3 py-2 border border-border rounded-md bg-background text-sm">
                                    <option value="">{{ t('common.all') || 'All' }}</option>
                                    <option value="true">{{ t('common.active') || 'Active' }}</option>
                                    <option value="false">{{ t('common.inactive') || 'Inactive' }}</option>
                                </select>
                            </div>

                            <!-- Warehouse Filter -->
                            <div>
                                <label class="text-xs font-medium text-muted-foreground mb-2 block">{{ t('stock.warehouse') }}</label>
                                <select v-model="warehouseId" class="w-full px-3 py-2 border border-border rounded-md bg-background text-sm">
                                    <option value="">{{ t('common.all') || 'All' }}</option>
                                    <option v-for="warehouse in warehouses" :key="(warehouse as any).id" :value="(warehouse as any).id.toString()">
                                        {{ (warehouse as any)[locale] }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="text-xs font-medium text-muted-foreground mb-2 block">{{ t('common.dateFrom') }}</label>
                                <Input v-model="movementDateFrom" type="date" class="w-full" />
                            </div>

                            <div>
                                <label class="text-xs font-medium text-muted-foreground mb-2 block">{{ t('common.dateTo') }}</label>
                                <Input v-model="movementDateTo" type="date" class="w-full" />
                            </div>
                        </div>

                        <!-- Action buttons -->
                        <div class="flex gap-2">
                            <Button @click="doSearch" variant="default" class="shrink-0">{{ t('common.search') }}</Button>
                            <Button @click="resetFilters" variant="outline" class="shrink-0">{{ t('common.reset') || 'Reset' }}</Button>
                        </div>
                    </div>
                </div>

                <!-- Export and Bulk Actions Section -->
                <div class="bg-card border border-border rounded-lg p-4 mb-6">
                    <h3 class="text-sm font-semibold mb-4">{{ t('common.export') || 'Export' }}</h3>
                    <div class="flex items-center gap-4 flex-wrap">
                        <!-- Export Buttons -->
                        <Button v-if="can('products.view')" @click="exportToExcel" :disabled="isExporting" variant="outline" class="gap-2">
                            <Download class="h-4 w-4" />{{ t('common.exportExcel') || 'Export to Excel' }}
                        </Button>
                        <Button v-if="can('products.view')" @click="exportToPdf" :disabled="isExporting" variant="outline" class="gap-2">
                            <FileText class="h-4 w-4" />{{ t('common.exportPdf') || 'Export to PDF' }}
                        </Button>
                        <Button v-if="can('products.view')" @click="printPdfExport" variant="outline" class="gap-2">
                            <Printer class="h-4 w-4" />{{ t('common.print') || 'Print PDF' }}
                        </Button>
                        
                        <!-- Bulk Delete Button -->
                        <Button v-if="can('products.delete') && selectedProducts.size > 0" @click="bulkDelete" variant="destructive" class="gap-2">
                            <Trash2 class="h-4 w-4" />{{ t('common.delete') }}
                        </Button>
                        
                        <!-- Clear Selection Button -->
                        <Button v-if="selectedProducts.size > 0" @click="clearSelections" variant="secondary" class="gap-2">
                            {{ t('common.clearSelection') || 'Clear Selection' }}
                        </Button>

                        <!-- Export Type Info - Right Aligned -->
                        <div class="text-xs text-muted-foreground ml-auto">
                            {{ selectedProducts.size > 0 ? t('common.selectedItems') || 'Selected Items' : t('common.filteredData') || 'Filtered Data' }}: 
                            <strong>{{ selectedProducts.size > 0 ? selectedProducts.size : 'All' }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Products Table -->
                <Table class="bg-transparent">
                    <TableHeader>
                        <TableRow class="border-b border-border hover:bg-muted/30">
                            <TableHead class="w-12 text-muted-foreground">
                                <button type="button" class="inline-flex h-5 w-5 items-center justify-center rounded border border-slate-400 bg-background" @click="toggleAllSelection">
                                    <Check v-if="isAllSelected || isIndeterminate" class="h-3.5 w-3.5 text-primary" />
                                </button>
                            </TableHead>
                            <TableHead class="text-muted-foreground">{{ t('common.name') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('common.category') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('common.quantity') }}</TableHead>
                            <TableHead v-if="hasMovementDateFilter" class="text-muted-foreground">{{ t('products.movementSummary') }}</TableHead>
                            <TableHead class="text-muted-foreground">{{ t('common.status') }}</TableHead>
                            <TableHead class="w-20 text-muted-foreground">{{ t('common.actions') }}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="p in products.data" :key="p.id" class="border-b border-border hover:bg-muted/30" :class="{ 'bg-slate-100 dark:bg-slate-800/50 ring-1 ring-primary/30': isProductSelected(p.id as number) }">
                            <TableCell class="w-12">
                                <button type="button" class="inline-flex h-5 w-5 items-center justify-center rounded border border-slate-400 bg-background" @click="() => toggleProductSelection(p.id as number)">
                                    <Check v-if="isProductSelected(p.id as number)" class="h-3.5 w-3.5 text-primary" />
                                </button>
                            </TableCell>
                            <TableCell class="font-medium">
                                <Link :href="`/warehouse/products/${p.id}`" class="hover:underline">
                                    {{ (p as any)[locale] || p.name_tr }} ({{ p.unit?.symbol ?? '-' }})
                                </Link>
                            </TableCell>
                            <TableCell>
                                <Badge v-if="p.category" variant="outline" class="border-dotted">{{ (p.category as any)?.[locale] || (p.category as any)?.name_tr || '-' }}</Badge>
                                <span v-else class="text-muted-foreground">-</span>
                            </TableCell>
                            <TableCell>{{ (p as any).stock_quantity ?? 0 }}</TableCell>
                            <TableCell v-if="hasMovementDateFilter" class="text-xs">
                                <div v-if="(p as any).movement_stats">
                                    <div>{{ t('products.movementsInRange') }}: {{ (p as any).movement_stats.count }}</div>
                                    <div>
                                        {{ t('nav.input') }}: {{ (p as any).movement_stats.in }} |
                                        {{ t('nav.output') }}: {{ (p as any).movement_stats.out }} |
                                        {{ t('common.transfer') }}: {{ (p as any).movement_stats.transfer }} |
                                        {{ t('common.adjustment') }}: {{ (p as any).movement_stats.adjustment }}
                                    </div>
                                    <div>
                                        {{ t('products.lastMovementDate') }}:
                                        {{ (p as any).movement_stats.last_date ? new Date((p as any).movement_stats.last_date).toLocaleString() : '-' }}
                                    </div>
                                </div>
                                <span v-else>-</span>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="p.is_active ? 'default' : 'secondary'">{{ p.is_active ? t('common.active') : t('common.inactive') }}</Badge>
                            </TableCell>
                            <TableCell>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon" title="Actions">
                                            <MoreHorizontal class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem as-child>
                                            <Link :href="`/warehouse/products/${p.id}`"><Eye class="mr-2 h-4 w-4" />{{ t('common.view') }}</Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem v-if="can('products.edit')" as-child>
                                            <Link :href="`/warehouse/products/${p.id}/edit`"><Pencil class="mr-2 h-4 w-4" />{{ t('common.edit') }}</Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem v-if="can('stock.transfer')" @click="openTransferModal(p as any)">
                                            <ArrowRightLeft class="mr-2 h-4 w-4" />{{ t('common.transfer') || 'Transfer' }}
                                        </DropdownMenuItem>
                                        <DropdownMenuItem v-if="can('products.delete')" class="text-destructive" @click="destroy(p.id)">
                                            <Trash2 class="mr-2 h-4 w-4" />{{ t('common.delete') }}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <Pagination v-if="products.links?.length" :links="products.links" class="mt-4" />
            </div>

            <Dialog v-model:open="transferModalOpen">
                <DialogContent class="sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>{{ t('common.transfer') || 'Transfer' }}</DialogTitle>
                        <DialogDescription>
                            {{ transferProduct ? ((transferProduct as any)[locale] || (transferProduct as any).name_tr) : '' }}
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitTransfer" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="space-y-2">
                                <Label>{{ t('stockMovements.fromWarehouse') }}</Label>
                                <div class="h-10 px-3 rounded-md border border-border bg-muted/40 flex items-center text-sm">
                                    {{ warehouseNameById(transferForm.from_warehouse_id) }}
                                </div>
                            </div>
                            <div class="space-y-2">
                                <Label>{{ t('stock.warehouse') }}</Label>
                                <SearchableSelect
                                    :model-value="transferForm.rows[0].warehouse_id"
                                    :options="warehouseOptions"
                                    :placeholder="t('common.select')"
                                    @update:model-value="(v) => transferForm.rows[0].warehouse_id = String(v ?? '')"
                                />
                                <p v-if="(transferForm.errors as any)['rows.0.warehouse_id']" class="text-xs text-destructive">{{ (transferForm.errors as any)['rows.0.warehouse_id'] }}</p>
                            </div>
                        </div>

                        <p v-if="transferForm.errors.from_warehouse_id" class="text-xs text-destructive">{{ transferForm.errors.from_warehouse_id }}</p>

                        <DialogFooter>
                            <Button type="button" variant="outline" @click="transferModalOpen = false">{{ t('common.cancel') }}</Button>
                            <Button type="submit" :disabled="transferForm.processing">{{ t('common.save') }}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppPageContent>
    </AppLayout>
</template>
