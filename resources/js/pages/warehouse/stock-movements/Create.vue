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
import AppPageContent from '@/components/AppPageContent.vue';
import { Card, CardContent } from '@/components/ui/card';
import { ArrowDownToLine, ArrowUpFromLine, Warehouse, Package, Banknote, Calendar, Info } from 'lucide-vue-next';

const props = defineProps<{ type: string; warehouses?: Array<Record<string, unknown>>; products?: Array<Record<string, unknown>> }>();
const { t } = useI18n();
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.stockMovements'), href: index.url() }, { title: t('stockMovements.addMovement') }];
const locale = computed(() => (useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en'));

const page = usePage();
const pageWarehouses = ((page.props as any).warehouses as Array<Record<string, unknown>> | undefined) ?? [];
const pageProducts = ((page.props as any).products as Array<Record<string, unknown>> | undefined) ?? [];
const warehouses = (props.warehouses as Array<Record<string, unknown>> | undefined) ?? pageWarehouses;
const products = (props.products as Array<Record<string, unknown>> | undefined) ?? pageProducts;

const form = useForm({
    warehouse_id: (warehouses[0] as any)?.id ?? '',
    product_id: '',
    type: props.type || 'in',
    quantity: '',
    unit_cost: '',
    from_warehouse_id: '',
    notes: '',
});

const filteredProducts = ref<Array<Record<string, any>>>([]);

const selectedProduct = computed(() => {
    if (!form.product_id) return null;
    const id = Number(form.product_id);
    return (
        products.find((p) => (p as any).id === id) ||
        filteredProducts.value.find((p: any) => p.id === id) ||
        null
    );
});

// availableProducts: prefer server filtered list when present, otherwise show all products
const availableProducts = computed(() => {
    if (filteredProducts.value && filteredProducts.value.length > 0) return filteredProducts.value;
    return products;
});

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
watch(selectedProduct, (product) => {
    if (!product) return;
    if ((product as any).unit_price) {
        form.unit_cost = String((product as any).unit_price);
    }

    // auto-select warehouse based on product stock balance with positive quantity
    const balances = (product as any).stockBalances as Array<Record<string, any>> | undefined;
    if (balances && balances.length > 0) {
        // For output/out type, prefer a warehouse with positive stock
        if (form.type === 'out') {
            const positive = balances.find((b) => Number(b.quantity) > 0);
            if (positive && positive.warehouse) {
                form.warehouse_id = String(positive.warehouse.id);
            } else if (balances[0]?.warehouse) {
                // fallback to first balance if none have stock
                form.warehouse_id = String(balances[0].warehouse.id);
            }
        } else if (form.type === 'transfer') {
            // for transfers, auto-select from_warehouse (source)
            const positive = balances.find((b) => Number(b.quantity) > 0);
            if (positive && positive.warehouse) {
                form.from_warehouse_id = String(positive.warehouse.id);
            }
        } else {
            // for input, auto-select warehouse to receive stock
            const positive = balances.find((b) => Number(b.quantity) > 0);
            if (positive && positive.warehouse) {
                form.warehouse_id = String(positive.warehouse.id);
            } else if (balances[0]?.warehouse) {
                form.warehouse_id = String(balances[0].warehouse.id);
            }
        }
    }
});

// Watch warehouse change to reset product selection
watch(() => form.warehouse_id, async (val) => {
    // Clear product when warehouse changes so user re-selects
    form.product_id = '';
    filteredProducts.value = [];
    const wid = Number(val);
    if (!wid) return;
    try {
        const res = await fetch(`/warehouse/stock-movements/products-by-warehouse?warehouse_id=${wid}`);
        if (res.ok) {
            const data = await res.json();
            filteredProducts.value = data as Array<Record<string, any>>;
        }
    } catch (e) {
        console.error('Failed loading products by warehouse', e);
    }
});

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
            <Card>
                <CardContent class="pt-6">
                    <form @submit.prevent="submit" class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2 md:col-span-2">
                            <Label class="flex items-center gap-2">
                                <Warehouse class="h-4 w-4 text-muted-foreground" />
                                {{ t('stock.warehouse') }}
                            </Label>
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    v-for="w in warehouses"
                                    :key="(w as any).id"
                                    type="button"
                                    :variant="form.warehouse_id === String((w as any).id) ? 'default' : 'outline'"
                                    size="sm"
                                    @click="form.warehouse_id = String((w as any).id)"
                                    class="h-auto px-3 py-3 text-xs cursor-pointer"
                                >
                                    {{ (w as any)[locale] }}
                                </Button>
                            </div>
                            <p v-if="form.errors.warehouse_id" class="text-sm text-destructive">
                                {{ form.errors.warehouse_id }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label for="product_id" class="flex items-center gap-2">
                                <Package class="h-4 w-4 text-muted-foreground" />
                                {{ t('stock.product') }}
                            </Label>
                            <select id="product_id" v-model="form.product_id" required class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option value="">-</option>
                                <option v-for="p in availableProducts" :key="(p as any).id" :value="(p as any).id">{{ (p as any)[locale] }}</option>
                            </select>
                            <p v-if="form.errors.product_id" class="text-sm text-destructive">
                                {{ form.errors.product_id }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label class="flex items-center gap-2">
                                <info class="h-4 w-4 text-muted-foreground" />
                                {{ t('common.quantity') }}
                            </Label>
                            <div class="flex items-center gap-2">
                                <Button type="button" variant="outline" size="sm" @click="decrementQuantity" class="h-10 w-10 p-0">-</Button>
                                <Input v-model="form.quantity" type="text" step="any" required class="text-center flex-1" />
                                <Button type="button" variant="outline" size="sm" @click="incrementQuantity" class="h-10 w-10 p-0">+</Button>
                            </div>
                            <p v-if="form.errors.quantity" class="text-sm text-destructive">
                                {{ form.errors.quantity }}
                            </p>
                        </div>
                        <div v-if="form.type === 'transfer'" class="space-y-2 md:col-span-2">
                            <Label for="from_warehouse_id" class="flex items-center gap-2">
                                <Warehouse class="h-4 w-4 text-muted-foreground" />
                                {{ t('stockMovements.fromWarehouse') }}
                            </Label>
                            <select id="from_warehouse_id" v-model="form.from_warehouse_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option value="">-</option>
                                <option v-for="w in warehouses" :key="(w as any).id" :value="(w as any).id">{{ (w as any)[locale] }}</option>
                            </select>
                            <p v-if="form.errors.from_warehouse_id" class="text-sm text-destructive">
                                {{ form.errors.from_warehouse_id }}
                            </p>
                        </div>
                        <div class="flex gap-2 md:col-span-2">
                            <Button type="submit" :disabled="form.processing">{{ t('common.save') }}</Button>
                            <Link :href="index.url()"><Button type="button" variant="outline">{{ t('common.cancel') }}</Button></Link>
                        </div>
                    </form>
                </CardContent>
            </Card>
            </div>
        </AppPageContent>
    </AppLayout>
</template>
