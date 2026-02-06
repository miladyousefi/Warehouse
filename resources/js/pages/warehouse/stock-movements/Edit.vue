<script setup lang="ts">
import { computed, watch, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppPageContent from '@/components/AppPageContent.vue';
import { Card, CardContent } from '@/components/ui/card';
import { ArrowDownToLine, ArrowUpFromLine, Warehouse, Package, Info } from 'lucide-vue-next';

const props = defineProps<{ movement: any; warehouses?: Array<Record<string, unknown>>; products?: Array<Record<string, unknown>> }>();
const { t } = useI18n();
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.stockMovements'), href: '/warehouse/stock-movements' }, { title: t('stockMovements.editMovement') }];
const locale = computed(() => (useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en'));

const page = usePage();
const pageWarehouses = ((page.props as any).warehouses as Array<Record<string, unknown>> | undefined) ?? [];
const pageProducts = ((page.props as any).products as Array<Record<string, unknown>> | undefined) ?? [];
const warehouses = (props.warehouses as Array<Record<string, unknown>> | undefined) ?? pageWarehouses;
const products = (props.products as Array<Record<string, unknown>> | undefined) ?? pageProducts;

const movement = props.movement || (page.props as any).movement;

const form = useForm({
    warehouse_id: movement?.warehouse_id ? String(movement.warehouse_id) : ((warehouses[0] as any)?.id ? String((warehouses[0] as any).id) : ''),
    product_id: movement?.product_id ? String(movement.product_id) : '',
    type: movement?.type ?? 'in',
    quantity: movement?.quantity ? String(movement.quantity) : '',
    unit_cost: movement?.unit_cost ? String(movement.unit_cost) : '',
    from_warehouse_id: movement?.from_warehouse_id ? String(movement.from_warehouse_id) : '',
    notes: movement?.notes ?? '',
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

const availableProducts = computed(() => {
    if (filteredProducts.value && filteredProducts.value.length > 0) return filteredProducts.value;
    return products;
});

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

watch(selectedProduct, (product) => {
    if (!product) return;
    if ((product as any).unit_price) {
        form.unit_cost = String((product as any).unit_price);
    }

    const balances = (product as any).stockBalances as Array<Record<string, any>> | undefined;
    if (balances && balances.length > 0) {
        if (form.type === 'out') {
            const positive = balances.find((b) => Number(b.quantity) > 0);
            if (positive && positive.warehouse) {
                form.warehouse_id = String(positive.warehouse.id);
            } else if (balances[0]?.warehouse) {
                form.warehouse_id = String(balances[0].warehouse.id);
            }
        } else if (form.type === 'transfer') {
            const positive = balances.find((b) => Number(b.quantity) > 0);
            if (positive && positive.warehouse) {
                form.from_warehouse_id = String(positive.warehouse.id);
            }
        } else {
            const positive = balances.find((b) => Number(b.quantity) > 0);
            if (positive && positive.warehouse) {
                form.warehouse_id = String(positive.warehouse.id);
            } else if (balances[0]?.warehouse) {
                form.warehouse_id = String(balances[0].warehouse.id);
            }
        }
    }
});

watch(() => form.warehouse_id, async (val) => {
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
    const id = movement?.id;
    if (!id) return;
    form.patch(`/warehouse/stock-movements/${id}`, {
        onError: (errors) => {
            console.error('Form errors:', errors);
        },
    });
}
</script>

<template>
    <Head :title="t('stockMovements.editMovement')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="p-4 md:p-6 pb-0">
                    <div class="flex items-center gap-3">
                        <div :class="`p-2 rounded-lg ${form.type === 'in' ? 'bg-emerald-100 dark:bg-emerald-900' : 'bg-rose-100 dark:bg-rose-900'}`">
                            <component :is="form.type === 'in' ? ArrowDownToLine : ArrowUpFromLine" :class="`h-6 w-6 ${form.type === 'in' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'}`" />
                        </div>
                        <div>
                            <h1 class="text-xl font-semibold">{{ form.type === 'in' ? t('nav.input') : form.type === 'out' ? t('nav.output') : t('stockMovements.editMovement') }}</h1>
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
                                    class="h-auto px-3 py-1 text-xs"
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
                                <Input v-model="form.quantity" type="number" step="any" required class="text-center flex-1" />
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
                            <Link href="/warehouse/stock-movements"><Button type="button" variant="outline">{{ t('common.cancel') }}</Button></Link>
                        </div>
                    </form>
                </CardContent>
            </Card>
            </div>
        </AppPageContent>
    </AppLayout>
</template>
