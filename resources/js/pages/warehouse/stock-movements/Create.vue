<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowDownToLine, ArrowUpFromLine, Warehouse, Package, Banknote, X, Asterisk } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { index, store } from '@/actions/App/Http/Controllers/Warehouse/StockMovementController';
import AppPageContent from '@/components/AppPageContent.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{ type: string; warehouses?: Array<Record<string, unknown>>; products?: Array<Record<string, unknown>>; suppliers?: Array<Record<string, unknown>> }>();
const { t } = useI18n();
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.stockMovements'), href: index.url() }, { title: t('stockMovements.addMovement') }];
const locale = computed(() => (useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en'));

const page = usePage();
const pageWarehouses = ((page.props as any).warehouses as Array<Record<string, unknown>> | undefined) ?? [];
const pageProducts = ((page.props as any).products as Array<Record<string, unknown>> | undefined) ?? [];
const pageSuppliers = ((page.props as any).suppliers as Array<Record<string, unknown>> | undefined) ?? [];
const warehouses = (props.warehouses as Array<Record<string, unknown>> | undefined) ?? pageWarehouses;
const products = (props.products as Array<Record<string, unknown>> | undefined) ?? pageProducts;
const suppliers = (props.suppliers as Array<Record<string, unknown>> | undefined) ?? pageSuppliers;
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

function getRowProducts(rowIndex: number): Array<Record<string, any>> {
    if (rowFilteredProducts.value[rowIndex] && rowFilteredProducts.value[rowIndex].length > 0) {
        return rowFilteredProducts.value[rowIndex];
    }
    return availableProducts.value;
}

const warehouseOptions = computed(() =>
    warehouses.map(w => ({
        id: (w as any).id,
        label: (w as any)[locale.value],
    }))
);

const supplierOptions = computed(() =>
    suppliers.map(s => ({
        id: (s as any).id,
        label: (s as any).name,
    }))
);

function getRowProductOptions(rowIndex: number) {
    return getRowProducts(rowIndex).map(p => ({
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
        if (positive?.warehouse) {
            row.warehouse_id = String(positive.warehouse.id);
        }
    } else if (form.type === 'transfer') {
        const positive = balances.find((b) => Number(b.quantity) > 0);
        if (positive?.warehouse) {
            row.from_warehouse_id = String(positive.warehouse.id);
        }
    } else {
        const positive = balances.find((b) => Number(b.quantity) > 0);
        if (positive?.warehouse) {
            row.warehouse_id = String(positive.warehouse.id);
        }
    }
}

function addRow() {
    form.rows.push(createEmptyRow());
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
        .then(res => res.json())
        .then(data => {
            rowFilteredProducts.value[rowIndex] = data as Array<Record<string, any>>;
        })
        .catch(() => {
            rowFilteredProducts.value[rowIndex] = [];
        });
}

function stepQty(row: any, diff: number) {
    const current = Number(row.quantity) || 0;
    const next = Math.max(0, current + diff);
    row.quantity = String(next);
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

onMounted(() => {
    const firstRow = form.rows[0];
    if (!firstRow) return;

    if (firstRow.warehouse_id) {
        onRowWarehouseChange(firstRow, 0);
    }

    if (firstRow.product_id) {
        onRowProductChange(firstRow, 0);
    }
});
</script>

<template>
    <Head :title="t('stockMovements.addMovement')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="p-4 md:p-6 pb-0">
                    <div class="flex flex-wrap items-center gap-3">
                        <div :class="`p-2 rounded-lg border ${form.type === 'in' ? 'bg-emerald-50 border-emerald-200' : 'bg-rose-50 border-rose-200'}`">
                            <component :is="form.type === 'in' ? ArrowDownToLine : ArrowUpFromLine" :class="`h-5 w-5 ${form.type === 'in' ? 'text-emerald-700' : 'text-rose-700'}`" />
                        </div>
                        <div>
                            <h1 class="text-xl font-semibold">{{ form.type === 'in' ? t('nav.input') : form.type === 'out' ? t('nav.output') : t('stockMovements.addMovement') }}</h1>
                            <p class="text-sm text-muted-foreground">{{ t('stockMovements.title') }}</p>
                        </div>
                    </div>
                </div>
            </template>

            <div class="p-4 md:p-6 pt-4 mx-auto w-full max-w-7xl">
                <form @submit.prevent="submit" class="space-y-2">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-muted-foreground">{{ t('common.item') }} {{ totalRows }}</p>
                        <Button type="button" variant="outline" @click.prevent="addRow">{{ t('common.addRow') || 'Add row' }}</Button>
                    </div>

                    <div class="rounded-md border bg-card p-3">
                        <div
                            v-for="(row, i) in form.rows"
                            :key="i"
                            class="p-0"
                            :style="i > 0 ? 'margin-top: 5px;' : ''"
                        >
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-[1.2fr_1.6fr_1.2fr_1fr_56px]">
                                <div>
                                    <SearchableSelect
                                        :model-value="row.warehouse_id"
                                        :options="warehouseOptions"
                                        :placeholder="t('common.select')"
                                        @update:model-value="(v) => { row.warehouse_id = v; onRowWarehouseChange(row, i); }"
                                    />
                                    <p v-if="(form.errors as any)[`rows.${i}.warehouse_id`]" class="text-xs text-destructive">{{ (form.errors as any)[`rows.${i}.warehouse_id`] }}</p>
                                </div>

                                <div>
                                    <SearchableSelect
                                        :model-value="row.product_id"
                                        :options="getRowProductOptions(i)"
                                        :placeholder="t('common.select')"
                                        @update:model-value="(v) => { row.product_id = v; onRowProductChange(row, i); }"
                                    />
                                    <p v-if="(form.errors as any)[`rows.${i}.product_id`]" class="text-xs text-destructive">{{ (form.errors as any)[`rows.${i}.product_id`] }}</p>
                                </div>

                                <div>
                                    <div class="flex items-center gap-1">
                                        <Button type="button" variant="outline" size="sm" @click="stepQty(row, -1)" class="h-9 min-w-9 px-2">−</Button>
                                        <div class="relative w-full">
                                            <button
                                                type="button"
                                                class="absolute left-1 top-1/2 -translate-y-1/2 h-7 w-7 inline-flex items-center justify-center rounded border border-border bg-background text-muted-foreground hover:bg-muted"
                                                @click="multiplyQty(row)"
                                                title="Multiply by price"
                                            >
                                                <Asterisk class="h-3.5 w-3.5" />
                                            </button>
                                            <Input v-model="row.quantity" type="number" step="any" required class="h-9 text-center pl-10" />
                                        </div>
                                        <Button type="button" variant="outline" size="sm" @click="stepQty(row, 1)" class="h-9 min-w-9 px-2">+</Button>
                                    </div>
                                    <p v-if="(form.errors as any)[`rows.${i}.quantity`]" class="text-xs text-destructive">{{ (form.errors as any)[`rows.${i}.quantity`] }}</p>
                                </div>

                                <div>
                                    <Input v-model="row.unit_cost" type="number" min="0" step="0.01" class="h-9" />
                                    <p v-if="(form.errors as any)[`rows.${i}.unit_cost`]" class="text-xs text-destructive">{{ (form.errors as any)[`rows.${i}.unit_cost`] }}</p>
                                </div>

                                <div class="flex items-end justify-end">
                                    <Button type="button" variant="ghost" size="sm" @click="removeRow(i)" class="h-9 w-9 p-0 text-destructive hover:text-destructive" :disabled="form.rows.length <= 1">
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div v-if="form.type !== 'out'" class="mt-3 border-t pt-3">
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label class="flex items-center gap-2"><Banknote class="h-4 w-4 text-muted-foreground" />{{ t('suppliers.supplier') }}</Label>
                                    <SearchableSelect :model-value="form.supplier_id" :options="supplierOptions" :placeholder="t('common.select')" @update:model-value="(v) => form.supplier_id = v" />
                                </div>
                                <div class="space-y-2">
                                    <Label class="flex items-center gap-2"><Package class="h-4 w-4 text-muted-foreground" />{{ t('stockMovements.factorNumber') || 'Factor Number' }}</Label>
                                    <Input v-model="form.factor_number" type="text" :placeholder="t('common.enter') + ' ' + (t('stockMovements.factorNumber') || 'Factor Number')" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="form.type === 'transfer'" class="space-y-2">
                        <Label for="from_warehouse_id" class="flex items-center gap-2"><Warehouse class="h-4 w-4 text-muted-foreground" />{{ t('stockMovements.fromWarehouse') }}</Label>
                        <SearchableSelect :model-value="form.from_warehouse_id" :options="warehouseOptions" :placeholder="t('common.select')" @update:model-value="(v) => form.from_warehouse_id = v" />
                        <p v-if="form.errors.from_warehouse_id" class="text-sm text-destructive">{{ form.errors.from_warehouse_id }}</p>
                    </div>

                    <div class="mt-6 pt-4 border-t">
                        <div class="mx-auto flex w-full max-w-7xl flex-col gap-3 sm:flex-row">
                            <Button type="submit" :disabled="form.processing" class="w-full sm:w-auto px-6">{{ t('common.save') }}</Button>
                            <Link :href="index.url()" class="w-full sm:w-auto"><Button type="button" variant="outline" class="w-full sm:w-auto px-6">{{ t('common.cancel') }}</Button></Link>
                        </div>
                    </div>
                </form>
            </div>
        </AppPageContent>
    </AppLayout>
</template>
