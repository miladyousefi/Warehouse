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
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import {
    index,
    store,
} from '@/actions/App/Http/Controllers/Warehouse/StockMovementController';
import AppPageContent from '@/components/AppPageContent.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Button } from '@/components/ui/button';
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
    ((page.props as any).warehouses as Array<Record<string, unknown>> | undefined) ?? [];
const pageProducts =
    ((page.props as any).products as Array<Record<string, unknown>> | undefined) ?? [];
const pageSuppliers =
    ((page.props as any).suppliers as Array<Record<string, unknown>> | undefined) ?? [];
const warehouses =
    (props.warehouses as Array<Record<string, unknown>> | undefined) ?? pageWarehouses;
const products =
    (props.products as Array<Record<string, unknown>> | undefined) ?? pageProducts;
const suppliers =
    (props.suppliers as Array<Record<string, unknown>> | undefined) ?? pageSuppliers;
const queryParams = new URLSearchParams(window.location.search);

function createEmptyRow() {
    return {
        warehouse_id: queryParams.get('warehouse_id') ?? '',
        product_id: queryParams.get('product_id') ?? '',
        quantity: '',
        unit_cost: '',
        from_warehouse_id: queryParams.get('from_warehouse_id') ?? '',
    };
}

const form = useForm({
    supplier_id: '',
    factor_number: '',
    type: props.type || 'in',
    notes: '',
    rows: [createEmptyRow()],
});

const rowFilteredProducts = ref<Record<number, Array<Record<string, any>>>>({});
const availableProducts = computed(() => products);
const totalRows = computed(() => form.rows.length);

function rowAvailableQuantity(row: any, rowIndex: number): number {
    if (!row?.product_id || !row?.warehouse_id) return 0;
    const product = rowSelectedProduct(row, rowIndex);
    if (!product) return 0;
    const wid = Number(row.warehouse_id);
    const balances = (product as any).stockBalances as Array<Record<string, any>> | undefined;
    if (Array.isArray(balances) && balances.length > 0) {
        const match = balances.find((b) => Number(b.warehouse_id ?? b.warehouse?.id) === wid);
        if (match) return Number(match.quantity) || 0;
    }
    if (Number((product as any).stock_quantity) && rowFilteredProducts.value[rowIndex]) {
        return Number((product as any).stock_quantity) || 0;
    }
    return 0;
}

function getRowProducts(rowIndex: number): Array<Record<string, any>> {
    if (rowFilteredProducts.value[rowIndex] && rowFilteredProducts.value[rowIndex].length > 0) {
        return rowFilteredProducts.value[rowIndex];
    }
    return availableProducts.value;
}

const warehouseOptions = computed(() =>
    warehouses.map((w) => ({ id: (w as any).id, label: (w as any)[locale.value] })),
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
        const filtered = rowFilteredProducts.value[rowIndex].find((p) => (p as any).id === id);
        if (filtered) return filtered;
    }
    return products.find((p) => (p as any).id === id) || null;
}

function onRowProductChange(row: any, rowIndex: number) {
    const product = rowSelectedProduct(row, rowIndex);
    if (!product) return;
    if ((product as any).unit_price !== null && (product as any).unit_price !== undefined) {
        row.unit_cost = String((product as any).unit_price);
    }
    const balances = (product as any).stockBalances as Array<Record<string, any>> | undefined;
    if (!balances || balances.length === 0) return;
    if (form.type === 'out') {
        const positive = balances.find((b) => Number(b.quantity) > 0);
        if (positive?.warehouse) row.warehouse_id = String(positive.warehouse.id);
    } else if (form.type === 'transfer') {
        const positive = balances.find((b) => Number(b.quantity) > 0);
        if (positive?.warehouse) row.from_warehouse_id = String(positive.warehouse.id);
    } else {
        const positive = balances.find((b) => Number(b.quantity) > 0);
        if (positive?.warehouse) row.warehouse_id = String(positive.warehouse.id);
    }
}

function addRow() { form.rows.push(createEmptyRow()); }
function removeRow(i: number) {
    if (form.rows.length <= 1) return;
    form.rows.splice(i, 1);
    delete rowFilteredProducts.value[i];
}

function onRowWarehouseChange(row: any, rowIndex: number) {
    row.product_id = '';
    const wid = Number(row.warehouse_id);
    if (!wid) { rowFilteredProducts.value[rowIndex] = []; return; }
    fetch(`/warehouse/products/search?warehouse_id=${wid}`)
        .then((res) => res.json())
        .then((data) => { rowFilteredProducts.value[rowIndex] = data as Array<Record<string, any>>; })
        .catch(() => { rowFilteredProducts.value[rowIndex] = []; });
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

function submit() { form.post(store.url()); }

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
                        <div :class="`rounded-lg border p-2 ${form.type === 'in' ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50'}`">
                            <component
                                :is="form.type === 'in' ? ArrowDownToLine : ArrowUpFromLine"
                                :class="`h-5 w-5 ${form.type === 'in' ? 'text-emerald-700' : 'text-rose-700'}`"
                            />
                        </div>
                        <div>
                            <h1 class="text-xl font-semibold">
                                {{ form.type === 'in' ? t('nav.input') : form.type === 'out' ? t('nav.output') : t('stockMovements.addMovement') }}
                            </h1>
                            <p class="text-sm text-muted-foreground">{{ t('stockMovements.title') }}</p>
                        </div>
                    </div>
                </div>
            </template>

            <div class="mx-auto w-full max-w-7xl p-4 pt-4 md:p-6">
                <form @submit.prevent="submit" class="space-y-3">

                    <!-- Top bar: count + add button -->
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-muted-foreground">
                            {{ t('common.item') }} {{ totalRows }}
                        </p>
                        <button
                            type="button"
                            @click.prevent="addRow"
                            class="inline-flex h-8 items-center gap-1.5 rounded-md border border-dashed border-border px-3 text-xs font-medium text-muted-foreground transition-colors hover:border-foreground/30 hover:bg-muted hover:text-foreground"
                        >
                            <Plus class="h-3.5 w-3.5" />
                            {{ t('common.addRow') || 'Add row' }}
                        </button>
                    </div>

                    <!-- Rows container -->
                    <div class="overflow-hidden rounded-lg border border-border/70">

                        <!-- Column headers — visible on xl -->
                        <div class="hidden border-b border-border/50 bg-muted/30 px-4 py-2 text-[11px] font-medium uppercase tracking-wider text-muted-foreground xl:grid xl:grid-cols-[1.2fr_1.6fr_1.2fr_1fr_44px]">
                            <div>{{ t('stock.warehouse') }}</div>
                            <div>{{ t('stock.product') }}</div>
                            <div class="text-center">{{ t('common.quantity') }}</div>
                            <div>{{ t('stock.unitCost') || 'Unit Cost' }}</div>
                            <div></div>
                        </div>

                        <!-- Each row -->
                        <div
                            v-for="(row, i) in form.rows"
                            :key="i"
                            class="group relative border-b border-border/40 px-4 py-2 last:border-b-0 hover:bg-muted/20"
                        >
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-[1.2fr_1.6fr_1.2fr_1fr_44px]">

                                <!-- Warehouse -->
                                <div class="flex flex-col gap-0.5">
                                    <SearchableSelect
                                        :model-value="row.warehouse_id"
                                        :options="warehouseOptions"
                                        :placeholder="t('common.select')"
                                        class="h-9 text-sm"
                                        @update:model-value="(v) => { row.warehouse_id = v; onRowWarehouseChange(row, i); }"
                                    />
                                    <p v-if="(form.errors as any)[`rows.${i}.warehouse_id`]" class="text-[10px] text-destructive">
                                        {{ (form.errors as any)[`rows.${i}.warehouse_id`] }}
                                    </p>
                                </div>

                                <!-- Product -->
                                <div class="flex flex-col gap-0.5">
                                    <SearchableSelect
                                        :model-value="row.product_id"
                                        :options="getRowProductOptions(i)"
                                        :placeholder="t('common.select')"
                                        class="h-9 text-sm"
                                        @update:model-value="(v) => { row.product_id = v; onRowProductChange(row, i); }"
                                    />
                                    <p v-if="(form.errors as any)[`rows.${i}.product_id`]" class="text-[10px] text-destructive">
                                        {{ (form.errors as any)[`rows.${i}.product_id`] }}
                                    </p>
                                </div>

                                <!-- Quantity -->
                                <div class="flex flex-col gap-0.5">
                                    <div class="flex h-9 items-stretch overflow-hidden rounded-md border border-input bg-background ring-offset-background focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-1">
                                        <button
                                            type="button"
                                            @click="stepQty(row, -1)"
                                            class="flex w-9 shrink-0 items-center justify-center border-r border-input text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                        ><Minus class="h-3 w-3" /></button>
                                        <div class="relative flex-1">
                                            <button
                                                type="button"
                                                @click="multiplyQty(row)"
                                                title="Multiply by price"
                                                class="absolute top-1/2 left-1 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded text-muted-foreground/50 transition-colors hover:bg-muted hover:text-muted-foreground"
                                            ><Asterisk class="h-2.5 w-2.5" /></button>
                                            <input
                                                v-model="row.quantity"
                                                type="number"
                                                step="any"
                                                required
                                                class="h-full w-full border-0 bg-transparent pl-7 pr-1 text-center text-sm outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                            />
                                        </div>
                                        <!-- Available stock badge inside the stepper, right side -->
                                        <div
                                            v-if="form.type === 'out' && row.product_id && row.warehouse_id"
                                            class="flex items-center border-l border-input px-2"
                                        >
                                            <span class="whitespace-nowrap text-[10px] tabular-nums text-muted-foreground">
                                                / <span class="font-semibold text-foreground">{{ rowAvailableQuantity(row, i) }}</span>
                                            </span>
                                        </div>
                                        <button
                                            type="button"
                                            @click="stepQty(row, 1)"
                                            class="flex w-9 shrink-0 items-center justify-center border-l border-input text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                        ><Plus class="h-3 w-3" /></button>
                                    </div>
                                    <p v-if="(form.errors as any)[`rows.${i}.quantity`]" class="text-[10px] text-destructive">
                                        {{ (form.errors as any)[`rows.${i}.quantity`] }}
                                    </p>
                                </div>

                                <!-- Unit cost -->
                                <div class="flex flex-col gap-0.5">
                                    <Input v-model="row.unit_cost" type="number" min="0" step="0.01" class="h-9 text-sm" />
                                    <p v-if="(form.errors as any)[`rows.${i}.unit_cost`]" class="text-[10px] text-destructive">
                                        {{ (form.errors as any)[`rows.${i}.unit_cost`] }}
                                    </p>
                                </div>

                                <!-- Remove -->
                                <div class="flex items-center justify-end xl:justify-center">
                                    <button
                                        type="button"
                                        @click="removeRow(i)"
                                        :disabled="form.rows.length <= 1"
                                        class="flex h-9 w-9 items-center justify-center rounded-md border border-rose-200 bg-rose-50 text-rose-500 transition-colors hover:border-rose-300 hover:bg-rose-100 hover:text-rose-700 disabled:pointer-events-none disabled:border-border disabled:bg-transparent disabled:text-muted-foreground/30"
                                    ><X class="h-3.5 w-3.5" /></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Supplier + Factor Number (for 'in' type) -->
                    <div v-if="form.type !== 'out'" class="rounded-lg border border-border/70 bg-muted/10 p-3">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                    <Banknote class="h-3.5 w-3.5" />{{ t('suppliers.supplier') }}
                                </Label>
                                <SearchableSelect
                                    :model-value="form.supplier_id"
                                    :options="supplierOptions"
                                    :placeholder="t('common.select')"
                                    class="h-9 text-sm"
                                    @update:model-value="(v) => (form.supplier_id = v)"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <Label class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                    <Package class="h-3.5 w-3.5" />{{ t('stockMovements.factorNumber') || 'Factor Number' }}
                                </Label>
                                <Input
                                    v-model="form.factor_number"
                                    type="text"
                                    class="h-9 text-sm"
                                    :placeholder="t('common.enter') + ' ' + (t('stockMovements.factorNumber') || 'Factor Number')"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Transfer: from warehouse -->
                    <div v-if="form.type === 'transfer'" class="space-y-1.5">
                        <Label for="from_warehouse_id" class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                            <Warehouse class="h-3.5 w-3.5" />{{ t('stockMovements.fromWarehouse') }}
                        </Label>
                        <SearchableSelect
                            :model-value="form.from_warehouse_id"
                            :options="warehouseOptions"
                            :placeholder="t('common.select')"
                            class="h-9 text-sm"
                            @update:model-value="(v) => (form.from_warehouse_id = v)"
                        />
                        <p v-if="form.errors.from_warehouse_id" class="text-xs text-destructive">
                            {{ form.errors.from_warehouse_id }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="border-t pt-4">
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <Button type="submit" :disabled="form.processing" class="h-9 px-6">
                                {{ t('common.save') }}
                            </Button>
                            <Link :href="index.url()">
                                <Button type="button" variant="outline" class="h-9 px-6">
                                    {{ t('common.cancel') }}
                                </Button>
                            </Link>
                        </div>
                    </div>

                </form>
            </div>
        </AppPageContent>
    </AppLayout>
</template>