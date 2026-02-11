<script setup lang="ts">
import { computed, watch, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { nowTurkeyDatetimeLocal } from '@/composables/useTurkeyDate';
import { index, store } from '@/actions/App/Http/Controllers/Warehouse/StockMovementController';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import SearchableSelect from '@/components/SearchableSelect.vue';
import AppPageContent from '@/components/AppPageContent.vue';
import { Card, CardContent } from '@/components/ui/card';
import { ArrowDownToLine, ArrowUpFromLine, Warehouse, Package, Banknote, Calendar, Info, X } from 'lucide-vue-next';

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

// Track filtered products per row
const rowFilteredProducts = ref<Record<number, Array<Record<string, any>>>>({});

// availableProducts: show all products for all rows
const availableProducts = computed(() => {
    return products;
});

// Get products for a specific row, prioritizing filtered results
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

const productOptions = computed(() =>
    availableProducts.value.map(p => ({
        id: (p as any).id,
        label: `${(p as any)[locale.value]} (${(p as any).unit?.[locale.value] || '-'})`,
        stock_quantity: (p as any).stock_quantity,
        unit_price: (p as any).unit_price,
    }))
);

function getRowProductOptions(rowIndex: number) {
    return getRowProducts(rowIndex).map(p => ({
        id: (p as any).id,
        label: `${(p as any)[locale.value] || (p as any).name_tr || (p as any).name_en} (${(p as any).unit?.[locale.value] || '-'})`,
        stock_quantity: (p as any).stock_quantity,
        unit_price: (p as any).unit_price,
    }));
}

function rowSelectedProduct(row: any, rowIndex?: number) {
    if (!row.product_id) return null;
    const id = Number(row.product_id);
    
    // First check filtered products if row index is provided
    if (rowIndex !== undefined && rowFilteredProducts.value[rowIndex]) {
        const filtered = rowFilteredProducts.value[rowIndex].find((p) => (p as any).id === id);
        if (filtered) return filtered;
    }
    
    // Then check all products
    return products.find((p) => (p as any).id === id) || null;
}

// Increment and decrement quantity
function incrementQuantity(): void {
    const current = Number(form.quantity) || 0;
    form.quantity = String(current + 1);
}

function decrementQuantity(): void {
    const current = Number(form.quantity) || 0;
    if (current > 0) {
        form.quantity = String(current - 1);
    }
}

// Watch for product selection and set unit_cost and warehouse/from_warehouse from product stock balances
// When a product is selected for a row, populate unit_cost and attempt to auto-select warehouse fields on that row
function onRowProductChange(row: any) {
    const product = rowSelectedProduct(row);
    if (!product) return;
    if ((product as any).unit_price) {
        row.unit_cost = String((product as any).unit_price);
    }

    const balances = (product as any).stockBalances as Array<Record<string, any>> | undefined;
    // Only auto-populate warehouse if stockBalances exist (products from initial load)
    // Skip for products from warehouse-filtered endpoint
    if (balances && balances.length > 0) {
        if (form.type === 'out') {
            const positive = balances.find((b) => Number(b.quantity) > 0);
            if (positive && positive.warehouse) {
                row.warehouse_id = String(positive.warehouse.id);
            } else if (balances[0]?.warehouse) {
                row.warehouse_id = String(balances[0].warehouse.id);
            }
        } else if (form.type === 'transfer') {
            const positive = balances.find((b) => Number(b.quantity) > 0);
            if (positive && positive.warehouse) {
                row.from_warehouse_id = String(positive.warehouse.id);
            }
        } else {
            const positive = balances.find((b) => Number(b.quantity) > 0);
            if (positive && positive.warehouse) {
                row.warehouse_id = String(positive.warehouse.id);
            } else if (balances[0]?.warehouse) {
                row.warehouse_id = String(balances[0].warehouse.id);
            }
        }
    }
}

// Watch warehouse change to reset product selection
// If global warehouse was used previously, keep original behavior. For per-row selection we fetch products when a row's warehouse changes below.

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
    form.rows.splice(i, 1);
}

function onRowWarehouseChange(row: any, rowIndex: number) {
    // Reset product selection when warehouse changes
    row.product_id = '';
    
    // Fetch products available in the selected warehouse
    const wid = Number(row.warehouse_id);
    if (!wid) {
        rowFilteredProducts.value[rowIndex] = [];
        return;
    }
    
    try {
        fetch(`/warehouse/products/search?warehouse_id=${wid}`)
            .then(res => res.json())
            .then(data => {
                rowFilteredProducts.value[rowIndex] = data as Array<Record<string, any>>;
            })
            .catch(e => {
                console.error('Failed loading products for warehouse', e);
                // Fallback: show all products
                rowFilteredProducts.value[rowIndex] = [];
            });
    } catch (e) {
        console.error('Failed loading products for warehouse', e);
    }
}

function submit() {
    form.post(store.url(), {
        onError: (errors) => {
            console.error('Form errors:', errors);
        },
    });
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

                    <form @submit.prevent="submit" class="grid gap-4 md:grid-cols-2">
                        <div v-for="(row, i) in form.rows" :key="i" class="md:col-span-2 p-4 border rounded-lg space-y-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-muted-foreground">{{ t('common.item') || 'Item' }} {{ i + 1 }}</span>
                                <Button type="button" variant="ghost" size="sm" @click="() => removeRow(i)" class="h-8 w-8 p-0 text-destructive hover:text-destructive">
                                    <X class="h-4 w-4" />
                                </Button>
                            </div>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="col-span-1 space-y-2">
                                    <Label class="flex items-center gap-2 text-xs font-medium">{{ t('stock.warehouse') }}</Label>
                                    <SearchableSelect :model-value="row.warehouse_id" :options="warehouseOptions" :placeholder="t('common.select')" @update:model-value="(v) => { row.warehouse_id = v; onRowWarehouseChange(row, i); }" />
                                </div>
                                <div class="col-span-1 space-y-2">
                                    <Label class="flex items-center gap-2 text-xs font-medium">{{ t('stock.product') }}</Label>
                                    <SearchableSelect :model-value="row.product_id" :options="getRowProductOptions(i)" :placeholder="t('common.select')" @update:model-value="(v) => { row.product_id = v; onRowProductChange(row); }" />
                                </div>
                                <div class="col-span-1 space-y-2">
                                    <Label class="flex items-center gap-2 text-xs font-medium">{{ t('common.quantity') }}</Label>
                                    <div class="flex items-center gap-1">
                                        <Button type="button" variant="outline" size="sm" @click="() => { row.quantity = String(Math.max(0, (Number(row.quantity) || 0) - 1)) }" class="h-10 px-2 flex-shrink-0">−</Button>
                                        <Input v-model="row.quantity" type="number" step="any" required class="text-center flex-1 h-10" />
                                        <Button type="button" variant="outline" size="sm" @click="() => { row.quantity = String((Number(row.quantity) || 0) + 1) }" class="h-10 px-2 flex-shrink-0">+</Button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <Button type="button" @click.prevent="addRow">{{ t('common.addRow') || 'Add row' }}</Button>
                        </div>
                        <div v-if="form.type === 'transfer'" class="space-y-2 md:col-span-2">
                            <Label for="from_warehouse_id" class="flex items-center gap-2">
                                <Warehouse class="h-4 w-4 text-muted-foreground" />
                                {{ t('stockMovements.fromWarehouse') }}
                            </Label>
                            <SearchableSelect :model-value="form.from_warehouse_id" :options="warehouseOptions" :placeholder="t('common.select')" @update:model-value="(v) => form.from_warehouse_id = v" />
                            <p v-if="form.errors.from_warehouse_id" class="text-sm text-destructive">
                                {{ form.errors.from_warehouse_id }}
                            </p>
                        </div>
                        <div v-if="form.type !== 'out'" class="space-y-2 md:col-span-2 pt-2 border-t">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label class="flex items-center gap-2">
                                        <Banknote class="h-4 w-4 text-muted-foreground" />
                                        {{ t('suppliers.supplier') }}
                                    </Label>
                                    <SearchableSelect :model-value="form.supplier_id" :options="supplierOptions" :placeholder="t('common.select')" @update:model-value="(v) => form.supplier_id = v" />
                                </div>
                                <div class="space-y-2">
                                    <Label class="flex items-center gap-2">
                                        <Package class="h-4 w-4 text-muted-foreground" />
                                        {{ t('stockMovements.factorNumber') || 'Factor Number' }}
                                    </Label>
                                    <Input v-model="form.factor_number" type="text" :placeholder="t('common.enter') + ' ' + (t('stockMovements.factorNumber') || 'Factor Number')" />
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2 md:col-span-2">
                            <Button type="submit" :disabled="form.processing">{{ t('common.save') }}</Button>
                            <Link :href="index.url()"><Button type="button" variant="outline">{{ t('common.cancel') }}</Button></Link>
                        </div>
                    </form>
               
            </div>
        </AppPageContent>
    </AppLayout>
</template>
