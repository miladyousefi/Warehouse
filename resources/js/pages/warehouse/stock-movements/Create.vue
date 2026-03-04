<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowDownToLine, ArrowUpFromLine, Warehouse, Package, Banknote, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
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

const form = useForm({
    supplier_id: '',
    factor_number: '',
    type: props.type || 'in',
    notes: '',
    rows: [
        {
            warehouse_id: '',
            product_id: '',
            quantity: '',
            unit_cost: '',
            from_warehouse_id: '',
        },
    ],
});

const rowFilteredProducts = ref<Record<number, Array<Record<string, any>>>>({});

const availableProducts = computed(() => products);

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
    form.rows.push({
        warehouse_id: '',
        product_id: '',
        quantity: '',
        unit_cost: '',
        from_warehouse_id: '',
    });
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

function submit() {
    form.post(store.url());
}
</script>

<template>
    <Head :title="t('stockMovements.addMovement')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="p-4 md:p-6 pb-0">
                    <div class="flex items-center gap-3">
                        <div :class="`p-2 rounded-lg ${form.type === 'in' ? 'bg-emerald-100 dark:bg-emerald-900' : 'bg-rose-100 dark:bg-rose-900'}`">
                            <component :is="form.type === 'in' ? ArrowDownToLine : ArrowUpFromLine" :class="`h-6 w-6 ${form.type === 'in' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'}`" />
                        </div>
                        <div>
                            <h1 class="text-xl font-semibold">{{ form.type === 'in' ? t('nav.input') : form.type === 'out' ? t('nav.output') : t('stockMovements.addMovement') }}</h1>
                            <p class="text-sm text-muted-foreground">{{ t('stockMovements.title') }}</p>
                        </div>
                    </div>
                </div>
            </template>

            <div class="p-4 md:p-6 pt-4">
                <form @submit.prevent="submit" class="space-y-4">
                    <div
                        v-for="(row, i) in form.rows"
                        :key="i"
                        class="rounded-lg border p-3 md:p-4 space-y-3"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-muted-foreground">{{ t('common.item') || 'Item' }} {{ i + 1 }}</span>
                            <Button type="button" variant="ghost" size="sm" @click="removeRow(i)" class="h-8 w-8 p-0 text-destructive hover:text-destructive" :disabled="form.rows.length <= 1">
                                <X class="h-4 w-4" />
                            </Button>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <div class="space-y-2">
                                <Label class="text-xs font-medium flex items-center gap-2"><Warehouse class="h-3.5 w-3.5" />{{ t('stock.warehouse') }}</Label>
                                <SearchableSelect
                                    :model-value="row.warehouse_id"
                                    :options="warehouseOptions"
                                    :placeholder="t('common.select')"
                                    @update:model-value="(v) => { row.warehouse_id = v; onRowWarehouseChange(row, i); }"
                                />
                                <p v-if="(form.errors as any)[`rows.${i}.warehouse_id`]" class="text-xs text-destructive">{{ (form.errors as any)[`rows.${i}.warehouse_id`] }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label class="text-xs font-medium flex items-center gap-2"><Package class="h-3.5 w-3.5" />{{ t('stock.product') }}</Label>
                                <SearchableSelect
                                    :model-value="row.product_id"
                                    :options="getRowProductOptions(i)"
                                    :placeholder="t('common.select')"
                                    @update:model-value="(v) => { row.product_id = v; onRowProductChange(row, i); }"
                                />
                                <p v-if="(form.errors as any)[`rows.${i}.product_id`]" class="text-xs text-destructive">{{ (form.errors as any)[`rows.${i}.product_id`] }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label class="text-xs font-medium">{{ t('common.quantity') }}</Label>
                                <div class="flex items-center gap-2">
                                    <Button type="button" variant="outline" size="sm" @click="stepQty(row, -1)" class="h-10 px-3">−</Button>
                                    <Input v-model="row.quantity" type="number" step="any" required class="h-10 text-center" />
                                    <Button type="button" variant="outline" size="sm" @click="stepQty(row, 1)" class="h-10 px-3">+</Button>
                                </div>
                                <p v-if="(form.errors as any)[`rows.${i}.quantity`]" class="text-xs text-destructive">{{ (form.errors as any)[`rows.${i}.quantity`] }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label class="text-xs font-medium">{{ t('stockMovements.unitCost') }}</Label>
                                <Input v-model="row.unit_cost" type="number" min="0" step="0.01" class="h-10" />
                                <p v-if="(form.errors as any)[`rows.${i}.unit_cost`]" class="text-xs text-destructive">{{ (form.errors as any)[`rows.${i}.unit_cost`] }}</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <Button type="button" variant="outline" @click.prevent="addRow">{{ t('common.addRow') || 'Add row' }}</Button>
                    </div>

                    <div v-if="form.type !== 'out'" class="rounded-lg border p-3 md:p-4">
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

                    <div v-if="form.type === 'transfer'" class="space-y-2">
                        <Label for="from_warehouse_id" class="flex items-center gap-2"><Warehouse class="h-4 w-4 text-muted-foreground" />{{ t('stockMovements.fromWarehouse') }}</Label>
                        <SearchableSelect :model-value="form.from_warehouse_id" :options="warehouseOptions" :placeholder="t('common.select')" @update:model-value="(v) => form.from_warehouse_id = v" />
                        <p v-if="form.errors.from_warehouse_id" class="text-sm text-destructive">{{ form.errors.from_warehouse_id }}</p>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row">
                        <Button type="submit" :disabled="form.processing">{{ t('common.save') }}</Button>
                        <Link :href="index.url()"><Button type="button" variant="outline" class="w-full sm:w-auto">{{ t('common.cancel') }}</Button></Link>
                    </div>
                </form>
            </div>
        </AppPageContent>
    </AppLayout>
</template>
