<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowDownToLine,
    ArrowUpFromLine,
    Warehouse,
    Package,
    Banknote,
    X,
    Asterisk,
    Plus,
    Minus,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import {
} from '@/actions/App/Http/Controllers/Warehouse/StockMovementController';
import { index, store } from '@/routes/warehouse/stock-movements';
import AppPageContent from '@/components/AppPageContent.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    type: string;
    warehouses?: Array<Record<string, unknown>>;
    products?: Array<Record<string, unknown>>;
    suppliers?: Array<Record<string, unknown>>;
}>();
const { t } = useI18n();
const breadcrumbs: BreadcrumbItem[] = [
    { title: t('nav.stockMovements'), href: index.url() },
    { title: t('stockMovements.addMovement') },
];
const locale = computed(() =>
    useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en',
);

const page = usePage();
const pageWarehouses =
    ((page.props as any).warehouses as
        | Array<Record<string, unknown>>
        | undefined) ?? [];
const pageProducts =
    ((page.props as any).products as
        | Array<Record<string, unknown>>
        | undefined) ?? [];
const pageSuppliers =
    ((page.props as any).suppliers as
        | Array<Record<string, unknown>>
        | undefined) ?? [];
const warehouses =
    (props.warehouses as Array<Record<string, unknown>> | undefined) ??
    pageWarehouses;
const products =
    (props.products as Array<Record<string, unknown>> | undefined) ??
    pageProducts;
const suppliers =
    (props.suppliers as Array<Record<string, unknown>> | undefined) ??
    pageSuppliers;
const queryParams = new URLSearchParams(window.location.search);

function createEmptyRow() {
    return {
        warehouse_id: queryParams.get('warehouse_id') ?? '',
        product_id: queryParams.get('product_id') ?? '',
        quantity: '',
        unit_cost: '',
    };
}

const form = useForm({
    supplier_id: '',
    factor_number: '',
    type: props.type || 'in',
    from_warehouse_id: queryParams.get('from_warehouse_id') ?? '',
    notes: '',
    rows: [createEmptyRow()],
});

const rowFilteredProducts = ref<Record<number, Array<Record<string, any>>>>({});
const availableProducts = computed(() => products);
const totalRows = computed(() => form.rows.length);
const rowsEndRef = ref<HTMLDivElement | null>(null);

const rowErrors = computed(() => {
    const errors = form.errors as Record<string, string>;
    return Object.entries(errors)
        .filter(([k]) => k.startsWith('rows.'))
        .slice(0, 6)
        .map(([, v]) => v);
});

function rowAvailableQuantity(row: any, rowIndex: number): number {
    if (!row?.product_id || !row?.warehouse_id) return 0;
    const product = rowSelectedProduct(row, rowIndex);
    if (!product) return 0;
    const wid = Number(row.warehouse_id);
    const balances = (product as any).stockBalances as
        | Array<Record<string, any>>
        | undefined;
    if (Array.isArray(balances) && balances.length > 0) {
        const match = balances.find(
            (b) => Number(b.warehouse_id ?? b.warehouse?.id) === wid,
        );
        if (match) return Number(match.quantity) || 0;
    }
    if (
        Number((product as any).stock_quantity) &&
        rowFilteredProducts.value[rowIndex]
    ) {
        return Number((product as any).stock_quantity) || 0;
    }
    return 0;
}

function getRowProducts(rowIndex: number): Array<Record<string, any>> {
    if (
        rowFilteredProducts.value[rowIndex] &&
        rowFilteredProducts.value[rowIndex].length > 0
    ) {
        return rowFilteredProducts.value[rowIndex];
    }
    return availableProducts.value;
}

const warehouseOptions = computed(() =>
    warehouses.map((w) => ({
        id: (w as any).id,
        label: (w as any)[locale.value],
    })),
);
const supplierOptions = computed(() =>
    suppliers.map((s) => ({ id: (s as any).id, label: (s as any).name })),
);

function getRowProductOptions(rowIndex: number) {
    return getRowProducts(rowIndex).map((p) => ({
        id: (p as any).id,
        label: `${(p as any)[locale.value] || (p as any).name_tr || (p as any).name_en} (${(p as any).unit?.[locale.value] || '-'})`,
        unit_price: (p as any).unit_price,
    }));
}

function rowSelectedProduct(row: any, rowIndex: number) {
    if (!row.product_id) return null;
    const id = Number(row.product_id);
    if (rowFilteredProducts.value[rowIndex]) {
        const filtered = rowFilteredProducts.value[rowIndex].find(
            (p) => (p as any).id === id,
        );
        if (filtered) return filtered;
    }
    return products.find((p) => (p as any).id === id) || null;
}

function onRowProductChange(row: any, rowIndex: number) {
    const product = rowSelectedProduct(row, rowIndex);
    if (!product) return;
    if (
        (product as any).unit_price !== null &&
        (product as any).unit_price !== undefined
    ) {
        row.unit_cost = String((product as any).unit_price);
    }
    const balances = (product as any).stockBalances as
        | Array<Record<string, any>>
        | undefined;
    if (!balances || balances.length === 0) return;
    if (form.type === 'out') {
        const positive = balances.find((b) => Number(b.quantity) > 0);
        if (positive?.warehouse)
            row.warehouse_id = String(positive.warehouse.id);
    } else if (form.type === 'transfer') {
        const positive = balances.find((b) => Number(b.quantity) > 0);
        if (positive?.warehouse)
            form.from_warehouse_id = String(positive.warehouse.id);
    } else {
        const positive = balances.find((b) => Number(b.quantity) > 0);
        if (positive?.warehouse)
            row.warehouse_id = String(positive.warehouse.id);
    }
}

async function addRow() {
    form.rows.push(createEmptyRow());
    await nextTick();
    rowsEndRef.value?.scrollIntoView?.({ behavior: 'smooth', block: 'end' });
}
function removeRow(i: number) {
    if (form.rows.length <= 1) return;
    form.rows.splice(i, 1);
    delete rowFilteredProducts.value[i];
}

function onRowWarehouseChange(row: any, rowIndex: number) {
    row.product_id = '';
    const wid = Number(row.warehouse_id);
    if (!wid) {
        rowFilteredProducts.value[rowIndex] = [];
        return;
    }
    fetch(`/warehouse/products/search?warehouse_id=${wid}`)
        .then((res) => res.json())
        .then((data) => {
            rowFilteredProducts.value[rowIndex] = data as Array<
                Record<string, any>
            >;
        })
        .catch(() => {
            rowFilteredProducts.value[rowIndex] = [];
        });
}

function stepQty(row: any, diff: number) {
    const current = Number(row.quantity) || 0;
    row.quantity = String(Math.max(0, current + diff));
}

function multiplyQty(row: any) {
    const current = Number(row.quantity) || 0;
    const unitCost = Number(row.unit_cost) || 0;
    if (current <= 0 || unitCost <= 0) return;
    row.quantity = String(current * unitCost);
}

function submit() {
    form.post(store.url());
}

const selectTriggerClass =
    'h-11 border border-border/70 bg-white/10 px-3 text-sm backdrop-blur-md shadow-none focus-within:ring-2 focus-within:ring-ring/50 dark:bg-white/5';

const selectPanelClass =
    'border border-border/70 bg-white/70 shadow-md backdrop-blur-xl dark:bg-black/35';

const notesClass =
    'min-h-28 w-full resize-y rounded-md border border-border/70 bg-white/10 px-3 py-2 text-sm backdrop-blur-md shadow-none outline-none focus:ring-2 focus:ring-ring/50 dark:bg-white/5';

onMounted(() => {
    const firstRow = form.rows[0];
    if (!firstRow) return;
    if (firstRow.warehouse_id) onRowWarehouseChange(firstRow, 0);
    if (firstRow.product_id) onRowProductChange(firstRow, 0);
});
</script>

<template>
    <Head :title="t('stockMovements.addMovement')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <!-- ✅ HEADER: untouched from original -->
            <template #header>
                <div class="p-4 pb-0 md:p-6">
                    <div class="flex flex-wrap items-center gap-3">
                        <div
                            class="rounded-md border border-border/70 bg-white/10 p-2 backdrop-blur-md dark:bg-white/5"
                        >
                            <component
                                :is="
                                    form.type === 'in'
                                        ? ArrowDownToLine
                                        : ArrowUpFromLine
                                "
                                class="h-5 w-5 text-primary/80"
                            />
                        </div>
                        <div>
                            <h1 class="text-xl font-semibold">
                                {{
                                    form.type === 'in'
                                        ? t('nav.input')
                                        : form.type === 'out'
                                          ? t('nav.output')
                                          : t('stockMovements.addMovement')
                                }}
                            </h1>
                            <p class="text-sm text-muted-foreground">
                                {{ t('stockMovements.title') }}
                            </p>
                        </div>
                    </div>
                </div>
            </template>

            <div class="mx-auto w-full max-w-7xl p-4 pt-4 md:p-6">
                <form @submit.prevent="submit" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <div
                                class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="space-y-0.5">
                                    <CardTitle class="text-base">
                                        {{ t('common.item') }}
                                        {{ totalRows }}
                                    </CardTitle>
                                    <p class="text-xs text-muted-foreground">
                                        {{ t('stockMovements.title') }}
                                    </p>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <div class="-mx-2 overflow-x-auto px-2">
                                    <div class="min-w-[980px] space-y-2">
                                        <div
                                            class="grid grid-cols-[1.2fr_1.8fr_170px_140px_44px] gap-2 px-2 text-[11px] font-medium tracking-wider text-muted-foreground uppercase"
                                        >
                                            <div>
                                                {{ t('stock.warehouse') }}
                                            </div>
                                            <div>{{ t('stock.product') }}</div>
                                            <div class="text-center">
                                                {{ t('common.quantity') }}
                                            </div>
                                            <div>
                                                {{
                                                    t(
                                                        'stockMovements.unitCost',
                                                    ) || 'Unit Cost'
                                                }}
                                            </div>
                                            <div></div>
                                        </div>

                                        <div
                                            v-for="(row, i) in form.rows"
                                            :key="i"
                                            class="grid grid-cols-[1.2fr_1.8fr_170px_140px_44px] items-center gap-2 rounded-md border border-border/70 bg-white/10 p-2 backdrop-blur-md dark:bg-white/5"
                                        >
                                            <SearchableSelect
                                                :model-value="row.warehouse_id"
                                                :options="warehouseOptions"
                                                :placeholder="
                                                    t('stock.warehouse')
                                                "
                                                trigger-class="h-10 border border-border/70 bg-white/10 px-3 text-sm backdrop-blur-md shadow-none focus-within:ring-2 focus-within:ring-ring/50 dark:bg-white/5"
                                                :panel-class="selectPanelClass"
                                                @update:model-value="
                                                    (v) => {
                                                        row.warehouse_id = v;
                                                        onRowWarehouseChange(
                                                            row,
                                                            i,
                                                        );
                                                    }
                                                "
                                            />

                                            <SearchableSelect
                                                :model-value="row.product_id"
                                                :options="
                                                    getRowProductOptions(i)
                                                "
                                                :placeholder="
                                                    t('stock.product')
                                                "
                                                trigger-class="h-10 border border-border/70 bg-white/10 px-3 text-sm backdrop-blur-md shadow-none focus-within:ring-2 focus-within:ring-ring/50 dark:bg-white/5"
                                                :panel-class="selectPanelClass"
                                                @update:model-value="
                                                    (v) => {
                                                        row.product_id = v;
                                                        onRowProductChange(
                                                            row,
                                                            i,
                                                        );
                                                    }
                                                "
                                            />

                                            <div
                                                class="flex h-10 items-stretch overflow-hidden rounded-md border border-border/70 bg-white/10 ring-offset-background backdrop-blur-md focus-within:ring-2 focus-within:ring-ring/50 dark:bg-white/5"
                                                :title="
                                                    form.type === 'out' &&
                                                    row.product_id &&
                                                    row.warehouse_id
                                                        ? `${t('common.available') || 'Available'}: ${rowAvailableQuantity(row, i)}`
                                                        : ''
                                                "
                                            >
                                                <button
                                                    type="button"
                                                    class="flex w-10 shrink-0 items-center justify-center border-r border-border/70 text-muted-foreground transition-colors hover:bg-primary/5 hover:text-foreground"
                                                    @click="stepQty(row, -1)"
                                                >
                                                    <Minus class="h-3 w-3" />
                                                </button>
                                                <div class="relative flex-1">
                                                    <button
                                                        type="button"
                                                        title="Multiply by price"
                                                        class="absolute top-1/2 left-1 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded text-muted-foreground/50 transition-colors hover:bg-muted hover:text-muted-foreground"
                                                        @click="
                                                            multiplyQty(row)
                                                        "
                                                    >
                                                        <Asterisk
                                                            class="h-2.5 w-2.5"
                                                        />
                                                    </button>
                                                    <input
                                                        v-model="row.quantity"
                                                        type="number"
                                                        step="any"
                                                        required
                                                        class="h-full w-full [appearance:textfield] border-0 bg-transparent pr-2 pl-8 text-center text-sm outline-none [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                                    />
                                                </div>
                                                <button
                                                    type="button"
                                                    class="flex w-10 shrink-0 items-center justify-center border-l border-border/70 text-muted-foreground transition-colors hover:bg-primary/5 hover:text-foreground"
                                                    @click="stepQty(row, 1)"
                                                >
                                                    <Plus class="h-3 w-3" />
                                                </button>
                                            </div>

                                            <Input
                                                v-model="row.unit_cost"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                class="h-10 text-sm"
                                            />

                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="icon-sm"
                                                class="border-border/70 bg-white/10 text-destructive shadow-none backdrop-blur-md hover:bg-destructive/10 hover:text-destructive dark:bg-white/5"
                                                :disabled="
                                                    form.rows.length <= 1
                                                "
                                                @click="removeRow(i)"
                                                :title="t('common.delete')"
                                            >
                                                <X class="h-3.5 w-3.5" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>

                                <Button
                                    type="button"
                                    variant="outline"
                                    class="w-full border-dashed border-border/70 bg-white/10 backdrop-blur-md dark:bg-white/5"
                                    @click.prevent="addRow"
                                >
                                    <Plus class="h-4 w-4" />
                                    {{ t('common.addRow') || 'Add row' }}
                                </Button>
                                <div ref="rowsEndRef" />

                                <div
                                    v-if="rowErrors.length"
                                    class="rounded-md border border-destructive/30 bg-destructive/5 px-3 py-2 text-xs text-destructive"
                                >
                                    <div class="font-medium">
                                        {{ t('common.error') }}
                                    </div>
                                    <ul class="mt-1 list-disc space-y-0.5 pl-4">
                                        <li
                                            v-for="(e, idx) in rowErrors"
                                            :key="idx"
                                        >
                                            {{ e }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-if="form.type !== 'out'">
                        <CardContent class="pt-6">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="space-y-1.5">
                                    <Label
                                        class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground"
                                    >
                                        <Banknote class="h-3.5 w-3.5" />{{
                                            t('suppliers.supplier')
                                        }}
                                    </Label>
                                    <SearchableSelect
                                        :model-value="form.supplier_id"
                                        :options="supplierOptions"
                                        :placeholder="t('common.select')"
                                        :trigger-class="selectTriggerClass"
                                        :panel-class="selectPanelClass"
                                        @update:model-value="
                                            (v) => (form.supplier_id = v)
                                        "
                                    />
                                </div>
                                <div class="space-y-1.5">
                                    <Label
                                        class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground"
                                    >
                                        <Package class="h-3.5 w-3.5" />{{
                                            t('stockMovements.factorNumber') ||
                                            'Factor Number'
                                        }}
                                    </Label>
                                    <Input
                                        v-model="form.factor_number"
                                        type="text"
                                        class="h-11 text-sm"
                                        :placeholder="
                                            t('common.enter') +
                                            ' ' +
                                            (t('stockMovements.factorNumber') ||
                                                'Factor Number')
                                        "
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-if="form.type === 'transfer'">
                        <CardContent class="pt-6">
                            <div class="space-y-1.5">
                                <Label
                                    for="from_warehouse_id"
                                    class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground"
                                >
                                    <Warehouse class="h-3.5 w-3.5" />{{
                                        t('stockMovements.fromWarehouse')
                                    }}
                                </Label>
                                <SearchableSelect
                                    :model-value="form.from_warehouse_id"
                                    :options="warehouseOptions"
                                    :placeholder="t('common.select')"
                                    :trigger-class="selectTriggerClass"
                                    :panel-class="selectPanelClass"
                                    @update:model-value="
                                        (v) => (form.from_warehouse_id = v)
                                    "
                                />
                                <p
                                    v-if="form.errors.from_warehouse_id"
                                    class="text-xs text-destructive"
                                >
                                    {{ form.errors.from_warehouse_id }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="pt-6">
                            <div class="space-y-1.5">
                                <Label
                                    for="notes"
                                    class="text-xs font-medium text-muted-foreground"
                                >
                                    {{ t('common.notes') }}
                                </Label>
                                <textarea
                                    id="notes"
                                    v-model="form.notes"
                                    :class="notesClass"
                                    :placeholder="t('common.enter')"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="h-10 w-full px-6 sm:w-auto"
                        >
                            {{ t('common.save') }}
                        </Button>
                        <Link :href="index.url()" class="w-full sm:w-auto">
                            <Button
                                type="button"
                                variant="outline"
                                class="h-10 w-full px-6 sm:w-auto"
                            >
                                {{ t('common.cancel') }}
                            </Button>
                        </Link>
                    </div>
                </form>
            </div>
        </AppPageContent>
    </AppLayout>
</template>
