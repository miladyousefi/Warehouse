<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Plus,
    Pencil,
    Trash2,
    Eye,
    MoreHorizontal,
    Download,
    FileText,
    Check,
    ArrowRightLeft,
    Printer,
} from 'lucide-vue-next';
import { ref, computed, watch, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import {
    create,
} from '@/actions/App/Http/Controllers/Warehouse/ProductController';
import { store as stockMovementStore } from '@/actions/App/Http/Controllers/Warehouse/StockMovementController';
import { index } from '@/routes/warehouse/products';
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
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
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { usePermission } from '@/composables/usePermission';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Props {
    products: {
        data: Array<Record<string, unknown>>;
        links: Array<{ url: string | null; label: string }>;
    };
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
const locale = computed(() =>
    useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en',
);
const breadcrumbs: BreadcrumbItem[] = [
    { title: t('nav.products'), href: index.url() },
];
const hasMovementDateFilter = computed(() =>
    Boolean(movementDateFrom.value || movementDateTo.value),
);

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
            console.error(
                'Failed to parse selected products from localStorage:',
                e,
            );
        }
    }
});

// Watch for changes to selectedProducts and persist to localStorage
watch(
    selectedProducts,
    (newSelection) => {
        const ids = Array.from(newSelection);
        localStorage.setItem(storageKey, JSON.stringify(ids));
    },
    { deep: true },
);

const filtersOpen = ref(false);
const exportOpen = ref(false);
const isExporting = ref(false);
const duplicatesOpen = ref(false);
const isCheckingDuplicates = ref(false);
const isMergingDuplicates = ref(false);
const isMergingAllDuplicates = ref(false);
const duplicateReport = ref<{
    name_tr: Array<{
        key: string;
        products: Array<{
            id: number;
            sku: string | null;
            name_tr: string;
            name_en: string;
            stock_balances: Array<{ warehouse_id: number; quantity: number }>;
        }>;
    }>;
    name_en: Array<{
        key: string;
        products: Array<{
            id: number;
            sku: string | null;
            name_tr: string;
            name_en: string;
            stock_balances: Array<{ warehouse_id: number; quantity: number }>;
        }>;
    }>;
} | null>(null);
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

const openFilters = () => {
    filtersOpen.value = true;
};

const openExport = () => {
    exportOpen.value = true;
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
    return (
        props.products.data.length > 0 &&
        selectedProducts.value.size === props.products.data.length
    );
});

const isIndeterminate = computed(() => {
    return (
        selectedProducts.value.size > 0 &&
        selectedProducts.value.size < props.products.data.length
    );
});

const isProductSelected = (id: number) => {
    return selectedProducts.value.has(id);
};

const warehouseOptions = computed(() =>
    props.warehouses.map((w: any) => ({
        id: String(w.id),
        label: w[locale.value] || w.name_tr || w.name_en || `#${w.id}`,
    })),
);

const warehouseNameById = (id: string | number | null | undefined) => {
    if (!id) return '-';
    const found = props.warehouses.find(
        (w: any) => String(w.id) === String(id),
    );
    return found
        ? (found as any)[locale.value] ||
              (found as any).name_tr ||
              (found as any).name_en ||
              '-'
        : '-';
};

const warehouseSortOrderById = computed(() => {
    const map = new Map<string, number>();
    props.warehouses.forEach((w: any) => {
        map.set(String(w.id), Number(w.sort_order ?? 0));
    });
    return map;
});

const productStockBalances = (product: any) => {
    const balances =
        (product as any)?.stockBalances ?? (product as any)?.stock_balances;
    return Array.isArray(balances) ? balances : [];
};

const sortedStockBalances = (product: any) => {
    const balances = [...productStockBalances(product)];
    const sortMap = warehouseSortOrderById.value;

    balances.sort((a: any, b: any) => {
        const sa = sortMap.get(String(a?.warehouse_id)) ?? 0;
        const sb = sortMap.get(String(b?.warehouse_id)) ?? 0;
        if (sa !== sb) return sa - sb;
        return String(a?.warehouse_id ?? '').localeCompare(
            String(b?.warehouse_id ?? ''),
        );
    });

    return balances;
};

const filterFieldClass =
    'h-11 rounded-xl border-amber-200/70 bg-white/70 shadow-sm backdrop-blur focus-visible:border-amber-400 focus-visible:ring-amber-400/25 dark:border-amber-200/30 dark:bg-white/10';

const filterSelectClass =
    'h-11 w-full rounded-xl border border-amber-200/70 bg-white/70 px-3 text-sm shadow-sm backdrop-blur focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/25 dark:border-amber-200/30 dark:bg-white/10';

const inlineSearchClass =
    'h-10 w-full rounded-none border-0 border-b border-slate-300/70 bg-transparent pl-2.5 pr-0 shadow-none focus-visible:ring-0 focus-visible:ring-offset-0 focus-visible:border-slate-500 dark:border-white/20 dark:focus-visible:border-white/40';

const formatQty = (value: unknown) => {
    const num = Number(value);
    if (!Number.isFinite(num)) return String(value ?? 0);
    return num.toLocaleString(undefined, { maximumFractionDigits: 4 });
};

let searchDebounce: number | null = null;
watch(search, () => {
    if (searchDebounce) window.clearTimeout(searchDebounce);
    searchDebounce = window.setTimeout(() => {
        doSearch();
    }, 350);
});

async function openDuplicateCheck() {
    duplicatesOpen.value = true;
    isCheckingDuplicates.value = true;
    duplicateReport.value = null;

    try {
        const res = await fetch('/warehouse/products/duplicate-names');
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        duplicateReport.value = await res.json();
    } catch (e) {
        console.error('Failed loading duplicate names report', e);
        duplicateReport.value = { name_tr: [], name_en: [] };
    } finally {
        isCheckingDuplicates.value = false;
    }
}

function getCsrfToken() {
    return (
        (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)
            ?.content ?? ''
    );
}

async function mergeDuplicateGroup(group: any) {
    if (!group?.products?.length || group.products.length < 2) return;
    if (!can('products.delete')) return;
    if (isMergingAllDuplicates.value) return;

    const ids = (group.products as Array<any>)
        .map((p) => Number(p.id))
        .filter((id) => Number.isFinite(id));
    const keepProductId = Math.min(...ids);
    const removeProductIds = ids.filter((id) => id !== keepProductId);

    if (
        !confirm(
            (t('products.mergeDuplicatesConfirm') as string) ||
                'Merge duplicates and delete extra products?',
        )
    ) {
        return;
    }

    const csrf = getCsrfToken();
    if (!csrf) {
        alert('CSRF token not found. Please refresh the page.');
        return;
    }

    isMergingDuplicates.value = true;
    try {
        const res = await fetch('/warehouse/products/merge-duplicates', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                Accept: 'application/json',
            },
            body: JSON.stringify({
                keep_product_id: keepProductId,
                remove_product_ids: removeProductIds,
            }),
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        await openDuplicateCheck();
        router.reload({ only: ['products'], preserveScroll: true });
    } catch (e) {
        console.error('Failed merging duplicates', e);
        alert('Failed merging duplicates. Check console for details.');
    } finally {
        isMergingDuplicates.value = false;
    }
}

function buildMergeComponentsFromReport(report: any): Array<{
    keep_product_id: number;
    remove_product_ids: number[];
}> {
    if (!report) return [];

    const groups: Array<number[]> = [];
    for (const list of [report.name_tr ?? [], report.name_en ?? []]) {
        for (const group of list) {
            const ids = (group?.products ?? [])
                .map((p: any) => Number(p.id))
                .filter((id: number) => Number.isFinite(id));
            if (ids.length >= 2) groups.push(ids);
        }
    }

    if (groups.length === 0) return [];

    // Union-Find to merge overlapping groups into connected components
    const parent = new Map<number, number>();
    const find = (x: number): number => {
        const p = parent.get(x) ?? x;
        if (p === x) return x;
        const root = find(p);
        parent.set(x, root);
        return root;
    };
    const union = (a: number, b: number) => {
        const ra = find(a);
        const rb = find(b);
        if (ra !== rb) parent.set(rb, ra);
    };

    for (const ids of groups) {
        for (const id of ids) {
            if (!parent.has(id)) parent.set(id, id);
        }
        for (let i = 1; i < ids.length; i++) union(ids[0], ids[i]);
    }

    const components = new Map<number, number[]>();
    for (const id of parent.keys()) {
        const root = find(id);
        const arr = components.get(root) ?? [];
        arr.push(id);
        components.set(root, arr);
    }

    return Array.from(components.values())
        .map((ids) => {
            const sorted = [...new Set(ids)].sort((a, b) => a - b);
            return {
                keep_product_id: sorted[0],
                remove_product_ids: sorted.slice(1),
            };
        })
        .filter((m) => m.remove_product_ids.length > 0);
}

async function mergeAllDuplicates() {
    if (!can('products.delete')) return;
    if (!duplicateReport.value) return;

    const merges = buildMergeComponentsFromReport(duplicateReport.value);
    if (merges.length === 0) {
        alert(
            (t('products.noDuplicatesToMerge') as string) ||
                'No duplicates to merge.',
        );
        return;
    }

    if (
        !confirm(
            (t('products.mergeDuplicatesAllConfirm') as string) ||
                'Merge and delete all duplicates?',
        )
    ) {
        return;
    }

    const csrf = getCsrfToken();
    if (!csrf) {
        alert('CSRF token not found. Please refresh the page.');
        return;
    }

    isMergingAllDuplicates.value = true;
    isMergingDuplicates.value = true;
    try {
        for (const merge of merges) {
            const res = await fetch('/warehouse/products/merge-duplicates', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify(merge),
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
        }

        await openDuplicateCheck();
        router.reload({ only: ['products'], preserveScroll: true });
    } catch (e) {
        console.error('Failed merging all duplicates', e);
        alert('Failed merging all duplicates. Check console for details.');
    } finally {
        isMergingDuplicates.value = false;
        isMergingAllDuplicates.value = false;
    }
}

function openTransferModal(product: Record<string, any>) {
    const balances = productStockBalances(product) as Array<
        Record<string, any>
    >;
    const fromByCurrentFilter = warehouseId.value
        ? balances.find(
              (b: any) => String(b.warehouse_id) === String(warehouseId.value),
          )
        : null;
    const fromPositive = balances.find((b: any) => Number(b.quantity) > 0);
    const defaultFromWarehouse = String(
        fromByCurrentFilter?.warehouse_id ??
            fromPositive?.warehouse_id ??
            balances[0]?.warehouse_id ??
            (product as any).warehouse_id ??
            warehouseId.value ??
            '',
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

function currentListQueryParams() {
    const params = new URLSearchParams(window.location.search);
    params.delete('page'); // export should ignore pagination
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
    router.get(
        index.url(),
        {
            search: search.value || undefined,
            category_id: categoryId.value || undefined,
            is_active: isActive.value || undefined,
            warehouse_id: warehouseId.value || undefined,
            movement_date_from: movementDateFrom.value || undefined,
            movement_date_to: movementDateTo.value || undefined,
        },
        { preserveState: false },
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

function applyFiltersFromModal() {
    doSearch();
    filtersOpen.value = false;
}

function resetFiltersFromModal() {
    resetFilters();
    filtersOpen.value = false;
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
            selectedProducts.value.forEach((id) =>
                form.append('product_ids[]', id),
            );

            const csrfToken = (
                document.querySelector(
                    'meta[name="csrf-token"]',
                ) as HTMLMetaElement
            )?.content;
            if (!csrfToken) {
                console.error('CSRF token not found in meta tag');
                alert(
                    'Error: Security token not found. Please refresh the page and try again.',
                );
                return;
            }

            const response = await fetch('/warehouse/products/export/excel', {
                method: 'POST',
                body: form,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
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
                console.error(
                    `Export failed with status ${response.status}:`,
                    response.statusText,
                );
                alert(`Export failed: ${response.statusText}`);
            }
        } else {
            // Export all filtered products
            const url = `/warehouse/products/export/excel?${currentListQueryParams().toString()}`;
            window.location.href = url;
        }
    } catch (error) {
        console.error('Export error:', error);
        alert(
            'An error occurred during export. Please check the console for details.',
        );
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
            selectedProducts.value.forEach((id) =>
                form.append('product_ids[]', id),
            );

            const csrfToken = (
                document.querySelector(
                    'meta[name="csrf-token"]',
                ) as HTMLMetaElement
            )?.content;
            if (!csrfToken) {
                console.error('CSRF token not found in meta tag');
                alert(
                    'Error: Security token not found. Please refresh the page and try again.',
                );
                return;
            }

            const response = await fetch('/warehouse/products/export/pdf', {
                method: 'POST',
                body: form,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
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
                console.error(
                    `Export failed with status ${response.status}:`,
                    response.statusText,
                );
                alert(`Export failed: ${response.statusText}`);
            }
        } else {
            // Export all filtered products
            const url = `/warehouse/products/export/pdf?${currentListQueryParams().toString()}`;
            window.location.href = url;
        }
    } catch (error) {
        console.error('Export error:', error);
        alert(
            'An error occurred during export. Please check the console for details.',
        );
    } finally {
        isExporting.value = false;
    }
};

const bulkDelete = async () => {
    if (selectedProducts.value.size === 0) {
        alert(t('common.selectItems') || 'Please select items to delete');
        return;
    }

    if (
        !confirm(
            t('common.confirmDelete') ||
                `Delete ${selectedProducts.value.size} item(s)?`,
        )
    ) {
        return;
    }

    form.post(route('warehouse.products.bulk-delete'), {
        data: {
            product_ids: Array.from(selectedProducts.value),
        },
        onSuccess: () => {
            selectedProducts.value.clear();
            localStorage.removeItem(storageKey);
            exportOpen.value = false;
            router.reload();
        },
        onError: (errors) => {
            console.error('Error:', errors);
        },
    });
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
    printPdfExport();
    exportOpen.value = false;
};

const clearSelectionsFromModal = () => {
    clearSelections();
    exportOpen.value = false;
};

function destroy(id: number): void {
    if (confirm(t('common.delete') + '?'))
        router.delete(`/warehouse/products/${id}`);
}

function printPdfExport() {
    if (selectedProducts.value.size > 0) {
        const csrfToken = (
            document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement
        )?.content;
        if (!csrfToken) {
            alert(
                'Error: Security token not found. Please refresh the page and try again.',
            );
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

    const params = currentListQueryParams();
    params.set('print', '1');
    window.open(
        `/warehouse/products/export/pdf?${params.toString()}`,
        '_blank',
    );
}
</script>

<template>
    <Head :title="t('products.title')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="flex flex-col gap-4 p-4 pb-0 md:p-6">
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <h1 class="text-xl font-semibold">
                                {{ t('products.title') }}
                            </h1>
                            <p class="text-sm text-muted-foreground">
                                {{ t('common.view') }}
                            </p>
                        </div>
                        <div
                            class="flex flex-wrap items-center gap-2 sm:justify-end"
                        >
                            <Button
                                size="sm"
                                variant="outline"
                                @click="openFilters"
                            >
                                {{ t('common.filters') || 'Filters' }}
                            </Button>
                            <Button
                                v-if="can('products.view')"
                                size="sm"
                                variant="outline"
                                @click="openDuplicateCheck"
                            >
                                {{ t('products.checkUniqueNames') }}
                            </Button>
                            <Button
                                v-if="can('products.view')"
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
                            <Link
                                v-if="can('products.create')"
                                :href="create.url()"
                            >
                                <Button size="sm">
                                    <Plus class="mr-2 h-4 w-4" />{{
                                        t('products.createProduct')
                                    }}
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </template>
            <div class="p-4 pt-2 md:p-6 md:pt-3">
                <Dialog v-model:open="filtersOpen">
                    <DialogContent class="sm:max-w-4xl lg:max-w-5xl">
                        <DialogHeader>
                            <DialogTitle>
                                {{ t('common.filters') || 'Filters' }}
                            </DialogTitle>
                            <DialogDescription>
                                {{
                                    locale === 'tr'
                                        ? 'Listeyi daraltın ve daha hızlı bulun.'
                                        : 'Narrow the list and find faster.'
                                }}
                            </DialogDescription>
                        </DialogHeader>

                        <div class="max-h-[70vh] overflow-y-auto pr-1">
                            <div
                                class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-6"
                            >
                                <div>
                                    <Label
                                        class="mb-2 block text-xs font-medium text-muted-foreground"
                                        >{{ t('common.category') }}</Label
                                    >
                                    <select
                                        v-model="categoryId"
                                        :class="filterSelectClass"
                                    >
                                        <option value="">
                                            {{
                                                t('common.selectAll') ||
                                                'Select All'
                                            }}
                                        </option>
                                        <option
                                            v-for="category in categories"
                                            :key="(category as any).id"
                                            :value="
                                                (category as any).id.toString()
                                            "
                                        >
                                            {{ (category as any)[locale] }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <Label
                                        class="mb-2 block text-xs font-medium text-muted-foreground"
                                        >{{ t('common.status') }}</Label
                                    >
                                    <select
                                        v-model="isActive"
                                        :class="filterSelectClass"
                                    >
                                        <option value="">
                                            {{ t('common.all') || 'All' }}
                                        </option>
                                        <option value="true">
                                            {{ t('common.active') || 'Active' }}
                                        </option>
                                        <option value="false">
                                            {{
                                                t('common.inactive') ||
                                                'Inactive'
                                            }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <Label
                                        class="mb-2 block text-xs font-medium text-muted-foreground"
                                        >{{ t('stock.warehouse') }}</Label
                                    >
                                    <select
                                        v-model="warehouseId"
                                        :class="filterSelectClass"
                                    >
                                        <option value="">
                                            {{ t('common.all') || 'All' }}
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
                                        >{{ t('common.dateFrom') }}</Label
                                    >
                                    <Input
                                        v-model="movementDateFrom"
                                        type="date"
                                        class="w-full"
                                        :class="filterFieldClass"
                                    />
                                </div>

                                <div>
                                    <Label
                                        class="mb-2 block text-xs font-medium text-muted-foreground"
                                        >{{ t('common.dateTo') }}</Label
                                    >
                                    <Input
                                        v-model="movementDateTo"
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
                            >
                                {{ t('common.reset') || 'Reset' }}
                            </Button>
                            <Button
                                variant="outline"
                                @click="() => (filtersOpen = false)"
                            >
                                {{ t('common.close') || 'Close' }}
                            </Button>
                            <Button @click="applyFiltersFromModal">
                                {{ t('common.search') || 'Search' }}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <Dialog v-model:open="duplicatesOpen">
                    <DialogContent class="sm:max-w-4xl">
                        <DialogHeader>
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <DialogTitle>
                                    {{ t('products.duplicateNames') }}
                                </DialogTitle>
                                <Button
                                    v-if="can('products.delete')"
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    :disabled="
                                        isCheckingDuplicates ||
                                        isMergingDuplicates
                                    "
                                    @click="mergeAllDuplicates"
                                >
                                    {{
                                        isMergingAllDuplicates
                                            ? t('products.mergingDuplicates')
                                            : t('products.mergeDuplicatesAll')
                                    }}
                                </Button>
                            </div>
                            <DialogDescription>
                                {{ t('products.duplicateNamesHelp') }}
                            </DialogDescription>
                        </DialogHeader>

                        <div class="max-h-[70vh] overflow-y-auto pr-1">
                            <div
                                v-if="isCheckingDuplicates"
                                class="text-sm text-muted-foreground"
                            >
                                {{ t('common.loading') || 'Loading...' }}
                            </div>

                            <div
                                v-else-if="
                                    duplicateReport &&
                                    duplicateReport.name_tr.length === 0 &&
                                    duplicateReport.name_en.length === 0
                                "
                                class="text-sm text-muted-foreground"
                            >
                                {{ t('products.noDuplicates') }}
                            </div>

                            <div v-else class="space-y-6">
                                <div
                                    v-if="
                                        duplicateReport &&
                                        duplicateReport.name_tr.length
                                    "
                                >
                                    <div class="mb-2 text-sm font-medium">
                                        {{ t('products.nameTrDuplicates') }}
                                    </div>
                                    <div class="space-y-3">
                                        <div
                                            v-for="group in duplicateReport?.name_tr"
                                            :key="group.key"
                                            class="rounded-lg border p-3"
                                        >
                                            <div
                                                class="flex items-center justify-between gap-2"
                                            >
                                                <div
                                                    class="text-sm font-semibold"
                                                >
                                                    {{ group.key }}
                                                </div>
                                                <Button
                                                    v-if="
                                                        can('products.delete')
                                                    "
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    :disabled="
                                                        isMergingDuplicates
                                                    "
                                                    @click="
                                                        mergeDuplicateGroup(
                                                            group,
                                                        )
                                                    "
                                                >
                                                    {{
                                                        isMergingDuplicates
                                                            ? t(
                                                                  'products.mergingDuplicates',
                                                              )
                                                            : t(
                                                                  'products.mergeDuplicates',
                                                              )
                                                    }}
                                                </Button>
                                            </div>
                                            <div class="mt-2 space-y-2">
                                                <div
                                                    v-for="p in group.products"
                                                    :key="p.id"
                                                    class="flex flex-col gap-1 rounded-md bg-muted/30 p-2 text-xs"
                                                >
                                                    <div
                                                        class="flex flex-wrap items-center gap-2"
                                                    >
                                                        <Link
                                                            class="font-mono hover:underline"
                                                            :href="`/warehouse/products/${p.id}`"
                                                        >
                                                            #{{ p.id }}
                                                        </Link>
                                                        <span
                                                            v-if="p.sku"
                                                            class="font-mono text-muted-foreground"
                                                        >
                                                            {{ p.sku }}
                                                        </span>
                                                        <span
                                                            class="text-muted-foreground"
                                                        >
                                                            {{ p.name_en }}
                                                        </span>
                                                    </div>
                                                    <div
                                                        v-if="
                                                            p.stock_balances
                                                                .length
                                                        "
                                                        class="flex flex-wrap gap-x-3 gap-y-1 text-muted-foreground"
                                                    >
                                                        <span
                                                            v-for="b in p.stock_balances"
                                                            :key="
                                                                b.warehouse_id
                                                            "
                                                        >
                                                            {{
                                                                warehouseNameById(
                                                                    b.warehouse_id,
                                                                )
                                                            }}:
                                                            {{
                                                                formatQty(
                                                                    b.quantity,
                                                                )
                                                            }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="
                                        duplicateReport &&
                                        duplicateReport.name_en.length
                                    "
                                >
                                    <div class="mb-2 text-sm font-medium">
                                        {{ t('products.nameEnDuplicates') }}
                                    </div>
                                    <div class="space-y-3">
                                        <div
                                            v-for="group in duplicateReport?.name_en"
                                            :key="group.key"
                                            class="rounded-lg border p-3"
                                        >
                                            <div
                                                class="flex items-center justify-between gap-2"
                                            >
                                                <div
                                                    class="text-sm font-semibold"
                                                >
                                                    {{ group.key }}
                                                </div>
                                                <Button
                                                    v-if="
                                                        can('products.delete')
                                                    "
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    :disabled="
                                                        isMergingDuplicates
                                                    "
                                                    @click="
                                                        mergeDuplicateGroup(
                                                            group,
                                                        )
                                                    "
                                                >
                                                    {{
                                                        isMergingDuplicates
                                                            ? t(
                                                                  'products.mergingDuplicates',
                                                              )
                                                            : t(
                                                                  'products.mergeDuplicates',
                                                              )
                                                    }}
                                                </Button>
                                            </div>
                                            <div class="mt-2 space-y-2">
                                                <div
                                                    v-for="p in group.products"
                                                    :key="p.id"
                                                    class="flex flex-col gap-1 rounded-md bg-muted/30 p-2 text-xs"
                                                >
                                                    <div
                                                        class="flex flex-wrap items-center gap-2"
                                                    >
                                                        <Link
                                                            class="font-mono hover:underline"
                                                            :href="`/warehouse/products/${p.id}`"
                                                        >
                                                            #{{ p.id }}
                                                        </Link>
                                                        <span
                                                            v-if="p.sku"
                                                            class="font-mono text-muted-foreground"
                                                        >
                                                            {{ p.sku }}
                                                        </span>
                                                        <span
                                                            class="text-muted-foreground"
                                                        >
                                                            {{ p.name_tr }}
                                                        </span>
                                                    </div>
                                                    <div
                                                        v-if="
                                                            p.stock_balances
                                                                .length
                                                        "
                                                        class="flex flex-wrap gap-x-3 gap-y-1 text-muted-foreground"
                                                    >
                                                        <span
                                                            v-for="b in p.stock_balances"
                                                            :key="
                                                                b.warehouse_id
                                                            "
                                                        >
                                                            {{
                                                                warehouseNameById(
                                                                    b.warehouse_id,
                                                                )
                                                            }}:
                                                            {{
                                                                formatQty(
                                                                    b.quantity,
                                                                )
                                                            }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button
                                variant="outline"
                                @click="duplicatesOpen = false"
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
                            v-model="search"
                            :placeholder="t('common.search') || 'Search...'"
                            :class="inlineSearchClass"
                            @keyup.enter="doSearch"
                        />
                    </div>
                </div>

                <Dialog v-model:open="exportOpen">
                    <DialogContent class="sm:max-w-2xl">
                        <DialogHeader>
                            <DialogTitle>
                                {{ t('common.export') || 'Export' }}
                            </DialogTitle>
                            <DialogDescription>
                                {{
                                    selectedProducts.size > 0
                                        ? locale === 'tr'
                                            ? 'Seçili ürünleri dışa aktarın veya toplu işlem yapın.'
                                            : 'Export selected products or perform bulk actions.'
                                        : locale === 'tr'
                                          ? 'Filtrelenmiş listeyi dışa aktarın.'
                                          : 'Export the filtered list.'
                                }}
                            </DialogDescription>
                        </DialogHeader>

                        <div class="space-y-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <Button
                                    v-if="can('products.view')"
                                    @click="exportExcelFromModal"
                                    :disabled="isExporting"
                                    variant="outline"
                                    class="gap-2"
                                >
                                    <Download class="h-4 w-4" />{{
                                        t('common.exportExcel') ||
                                        'Export to Excel'
                                    }}
                                </Button>
                                <Button
                                    v-if="can('products.view')"
                                    @click="exportPdfFromModal"
                                    :disabled="isExporting"
                                    variant="outline"
                                    class="gap-2"
                                >
                                    <FileText class="h-4 w-4" />{{
                                        t('common.exportPdf') || 'Export to PDF'
                                    }}
                                </Button>
                                <Button
                                    v-if="can('products.view')"
                                    @click="printPdfFromModal"
                                    variant="outline"
                                    class="gap-2"
                                >
                                    <Printer class="h-4 w-4" />{{
                                        t('common.print') || 'Print PDF'
                                    }}
                                </Button>

                                <div
                                    class="ml-auto text-xs text-muted-foreground"
                                >
                                    {{
                                        selectedProducts.size > 0
                                            ? t('common.selectedItems') ||
                                              'Selected Items'
                                            : t('common.filteredData') ||
                                              'Filtered Data'
                                    }}:
                                    <strong>{{
                                        selectedProducts.size > 0
                                            ? selectedProducts.size
                                            : 'All'
                                    }}</strong>
                                </div>
                            </div>

                            <div
                                v-if="selectedProducts.size > 0"
                                class="flex flex-wrap items-center gap-3"
                            >
                                <Button
                                    v-if="can('products.delete')"
                                    @click="bulkDelete"
                                    variant="destructive"
                                    class="gap-2"
                                >
                                    <Trash2 class="h-4 w-4" />{{
                                        t('common.delete')
                                    }}
                                </Button>
                                <Button
                                    @click="clearSelectionsFromModal"
                                    variant="secondary"
                                    class="gap-2"
                                >
                                    {{
                                        t('common.clearSelection') ||
                                        'Clear Selection'
                                    }}
                                </Button>
                            </div>
                        </div>

                        <DialogFooter class="flex justify-end gap-2">
                            <Button
                                variant="outline"
                                @click="() => (exportOpen = false)"
                            >
                                {{ t('common.close') || 'Close' }}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <!-- Products Table -->
                <Table>
                    <TableHeader>
                        <TableRow
                            class="border-b border-border hover:bg-muted/30"
                        >
                            <TableHead class="w-12 text-muted-foreground">
                                <button
                                    type="button"
                                    class="inline-flex h-5 w-5 items-center justify-center rounded border border-slate-400 bg-background"
                                    @click="toggleAllSelection"
                                >
                                    <Check
                                        v-if="isAllSelected || isIndeterminate"
                                        class="h-3.5 w-3.5 text-primary"
                                    />
                                </button>
                            </TableHead>
                            <TableHead class="text-muted-foreground">{{
                                t('common.name')
                            }}</TableHead>
                            <TableHead
                                class="hidden text-muted-foreground md:table-cell"
                                >{{ t('common.category') }}</TableHead
                            >
                            <TableHead class="text-muted-foreground">{{
                                t('common.quantity')
                            }}</TableHead>
                            <TableHead
                                v-if="hasMovementDateFilter"
                                class="hidden text-muted-foreground lg:table-cell"
                                >{{ t('products.movementSummary') }}</TableHead
                            >
                            <TableHead
                                class="hidden text-muted-foreground sm:table-cell"
                                >{{ t('common.status') }}</TableHead
                            >
                            <TableHead class="w-20 text-muted-foreground">{{
                                t('common.actions')
                            }}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="p in products.data"
                            :key="p.id"
                            class="border-b border-border hover:bg-muted/30"
                        >
                            <TableCell class="w-12">
                                <button
                                    type="button"
                                    class="inline-flex h-5 w-5 items-center justify-center rounded border border-slate-400 bg-background"
                                    @click="
                                        () =>
                                            toggleProductSelection(
                                                p.id as number,
                                            )
                                    "
                                >
                                    <Check
                                        v-if="isProductSelected(p.id as number)"
                                        class="h-3.5 w-3.5 text-primary"
                                    />
                                </button>
                            </TableCell>
                            <TableCell class="font-medium">
                                <Link
                                    :href="`/warehouse/products/${p.id}`"
                                    class="hover:underline"
                                >
                                    {{ (p as any)[locale] || p.name_tr }} ({{
                                        p.unit?.symbol ?? '-'
                                    }})
                                </Link>
                                <div
                                    class="mt-0.5 text-xs text-muted-foreground"
                                >
                                    <span class="font-mono">#{{ p.id }}</span>
                                    <span v-if="(p as any).sku">
                                        ·
                                        <span class="font-mono">{{
                                            (p as any).sku
                                        }}</span>
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell class="hidden md:table-cell">
                                <Badge
                                    v-if="p.category"
                                    variant="outline"
                                    class="border-dotted"
                                    >{{
                                        (p.category as any)?.[locale] ||
                                        (p.category as any)?.name_tr ||
                                        '-'
                                    }}</Badge
                                >
                                <span v-else class="text-muted-foreground"
                                    >-</span
                                >
                            </TableCell>
                            <TableCell>
                                <TooltipProvider>
                                    <Tooltip
                                        v-if="
                                            productStockBalances(p as any)
                                                .length > 0
                                        "
                                        :delay-duration="150"
                                    >
                                        <TooltipTrigger as-child>
                                            <span
                                                class="inline-flex cursor-default items-center font-medium underline decoration-dotted underline-offset-4"
                                                :title="
                                                    t(
                                                        'products.stockByWarehouse',
                                                    )
                                                "
                                            >
                                                {{
                                                    formatQty(
                                                        (p as any)
                                                            .stock_quantity ??
                                                            0,
                                                    )
                                                }}
                                            </span>
                                        </TooltipTrigger>
                                        <TooltipContent
                                            side="bottom"
                                            align="start"
                                            class="min-w-56 rounded-md border bg-popover p-3 text-sm text-popover-foreground shadow-md"
                                        >
                                            <div
                                                class="mb-2 text-xs font-medium text-muted-foreground"
                                            >
                                                {{
                                                    t(
                                                        'products.stockByWarehouse',
                                                    )
                                                }}
                                            </div>
                                            <div class="space-y-1">
                                                <div
                                                    v-for="b in sortedStockBalances(
                                                        p as any,
                                                    )"
                                                    :key="b.id"
                                                    class="flex items-center justify-between gap-4 text-xs"
                                                >
                                                    <span class="truncate">
                                                        {{
                                                            warehouseNameById(
                                                                (b as any)
                                                                    .warehouse_id,
                                                            )
                                                        }}
                                                    </span>
                                                    <span
                                                        class="font-mono tabular-nums"
                                                    >
                                                        {{
                                                            formatQty(
                                                                (b as any)
                                                                    .quantity,
                                                            )
                                                        }}
                                                    </span>
                                                </div>
                                            </div>
                                        </TooltipContent>
                                    </Tooltip>
                                    <div v-else class="font-medium">
                                        {{
                                            formatQty(
                                                (p as any).stock_quantity ?? 0,
                                            )
                                        }}
                                    </div>
                                </TooltipProvider>
                            </TableCell>
                            <TableCell
                                v-if="hasMovementDateFilter"
                                class="hidden text-xs lg:table-cell"
                            >
                                <div v-if="(p as any).movement_stats">
                                    <div>
                                        {{ t('products.movementsInRange') }}:
                                        {{ (p as any).movement_stats.count }}
                                    </div>
                                    <div>
                                        {{ t('nav.input') }}:
                                        {{ (p as any).movement_stats.in }} |
                                        {{ t('nav.output') }}:
                                        {{ (p as any).movement_stats.out }} |
                                        {{ t('common.transfer') }}:
                                        {{ (p as any).movement_stats.transfer }}
                                        | {{ t('common.adjustment') }}:
                                        {{
                                            (p as any).movement_stats.adjustment
                                        }}
                                    </div>
                                    <div>
                                        {{ t('products.lastMovementDate') }}:
                                        {{
                                            (p as any).movement_stats.last_date
                                                ? new Date(
                                                      (p as any).movement_stats
                                                          .last_date,
                                                  ).toLocaleString()
                                                : '-'
                                        }}
                                    </div>
                                </div>
                                <span v-else>-</span>
                            </TableCell>
                            <TableCell class="hidden sm:table-cell">
                                <Badge
                                    :variant="
                                        p.is_active ? 'default' : 'secondary'
                                    "
                                    >{{
                                        p.is_active
                                            ? t('common.active')
                                            : t('common.inactive')
                                    }}</Badge
                                >
                            </TableCell>
                            <TableCell>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            title="Actions"
                                        >
                                            <MoreHorizontal class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem as-child>
                                            <Link
                                                :href="`/warehouse/products/${p.id}`"
                                                ><Eye class="mr-2 h-4 w-4" />{{
                                                    t('common.view')
                                                }}</Link
                                            >
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            v-if="can('products.edit')"
                                            as-child
                                        >
                                            <Link
                                                :href="`/warehouse/products/${p.id}/edit`"
                                                ><Pencil
                                                    class="mr-2 h-4 w-4"
                                                />{{ t('common.edit') }}</Link
                                            >
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            v-if="can('stock.transfer')"
                                            @click="openTransferModal(p as any)"
                                        >
                                            <ArrowRightLeft
                                                class="mr-2 h-4 w-4"
                                            />{{
                                                t('common.transfer') ||
                                                'Transfer'
                                            }}
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            v-if="can('products.delete')"
                                            class="text-destructive"
                                            @click="destroy(p.id)"
                                        >
                                            <Trash2 class="mr-2 h-4 w-4" />{{
                                                t('common.delete')
                                            }}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <div
                    class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground"
                >
                    <div>
                        {{ t('common.selectedItems') || 'Selected Items' }}:
                        <span class="font-semibold text-foreground">{{
                            selectedProducts.size
                        }}</span>
                    </div>
                    <div>
                        {{
                            locale === 'tr'
                                ? 'Seçim cihazınızda saklanır.'
                                : 'Selection is saved on your device.'
                        }}
                    </div>
                </div>
                <Pagination
                    v-if="products.links?.length"
                    :links="products.links"
                    class="mt-4"
                />
            </div>

            <Dialog v-model:open="transferModalOpen">
                <DialogContent class="sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>{{
                            t('common.transfer') || 'Transfer'
                        }}</DialogTitle>
                        <DialogDescription>
                            {{
                                transferProduct
                                    ? (transferProduct as any)[locale] ||
                                      (transferProduct as any).name_tr
                                    : ''
                            }}
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitTransfer" class="space-y-4">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label>{{
                                    t('stockMovements.fromWarehouse')
                                }}</Label>
                                <div
                                    class="flex h-10 items-center rounded-md border border-border bg-muted/40 px-3 text-sm"
                                >
                                    {{
                                        warehouseNameById(
                                            transferForm.from_warehouse_id,
                                        )
                                    }}
                                </div>
                            </div>
                            <div class="space-y-2">
                                <Label>{{ t('stock.warehouse') }}</Label>
                                <SearchableSelect
                                    :model-value="
                                        transferForm.rows[0].warehouse_id
                                    "
                                    :options="warehouseOptions"
                                    :placeholder="t('common.select')"
                                    @update:model-value="
                                        (v) =>
                                            (transferForm.rows[0].warehouse_id =
                                                String(v ?? ''))
                                    "
                                />
                                <p
                                    v-if="
                                        (transferForm.errors as any)[
                                            'rows.0.warehouse_id'
                                        ]
                                    "
                                    class="text-xs text-destructive"
                                >
                                    {{
                                        (transferForm.errors as any)[
                                            'rows.0.warehouse_id'
                                        ]
                                    }}
                                </p>
                            </div>
                        </div>

                        <p
                            v-if="transferForm.errors.from_warehouse_id"
                            class="text-xs text-destructive"
                        >
                            {{ transferForm.errors.from_warehouse_id }}
                        </p>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                @click="transferModalOpen = false"
                                >{{ t('common.cancel') }}</Button
                            >
                            <Button
                                type="submit"
                                :disabled="transferForm.processing"
                                >{{ t('common.save') }}</Button
                            >
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppPageContent>
    </AppLayout>
</template>
