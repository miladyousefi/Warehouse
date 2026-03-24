<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Package, Banknote, Plus, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import {
} from '@/actions/App/Http/Controllers/Warehouse/ProductController';
import { index, update } from '@/routes/warehouse/products';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const { t } = useI18n();
const locale = computed(() =>
    useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en',
);

const props = defineProps<{
    product: Record<string, unknown>;
    warehouses?: Array<Record<string, unknown>>;
    units?: Array<Record<string, unknown>>;
    categories?: Array<Record<string, unknown>>;
    stock_balances?: Array<{ warehouse_id: number; quantity: number }>;
}>();

const page = usePage();
const pageWarehouses =
    ((page.props as any).warehouses as
        | Array<Record<string, unknown>>
        | undefined) ?? [];
const pageUnits =
    ((page.props as any).units as Array<Record<string, unknown>> | undefined) ??
    [];
const pageCategories =
    ((page.props as any).categories as
        | Array<Record<string, unknown>>
        | undefined) ?? [];
const warehouses =
    (props.warehouses as Array<Record<string, unknown>> | undefined) ??
    pageWarehouses;
const units =
    (props.units as Array<Record<string, unknown>> | undefined) ?? pageUnits;
const categories =
    (props.categories as Array<Record<string, unknown>> | undefined) ??
    pageCategories;

const categoryOptions = computed(() =>
    categories.map((c) => ({
        id: (c as any).id,
        label: (c as any)[locale.value],
    })),
);

const unitOptions = computed(() =>
    units.map((u) => ({
        id: (u as any).id,
        label: (u as any)[locale.value],
    })),
);

const warehouseOptions = computed(() =>
    warehouses.map((w) => ({
        id: (w as any).id,
        label: (w as any)[locale.value],
    })),
);

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('nav.products'), href: index.url() },
    { title: t('products.editProduct') },
];

const isAddingWarehouse = ref(false);
const newWarehouseId = ref<number | ''>('');

const form = useForm({
    name: String(props.product.name_en ?? ''),
    category_id: (props.product as any).category_id ?? '',
    unit_id: (props.product as any).unit_id ?? (units[0] as any)?.id ?? '',
    unit_price: String(props.product.unit_price ?? ''),
    stock_balances: (props.stock_balances ?? []).map((row) => ({
        warehouse_id: Number(row.warehouse_id),
        quantity: String(row.quantity ?? 0),
    })),
});

const existingWarehouseIds = computed(() =>
    (form.stock_balances ?? []).map((r: any) => Number(r.warehouse_id)),
);

const availableWarehouseOptions = computed(() =>
    warehouseOptions.value.filter(
        (w) => !existingWarehouseIds.value.includes(Number((w as any).id)),
    ),
);

function addWarehouseRow(warehouseId: number) {
    if (!warehouseId) return;
    if (existingWarehouseIds.value.includes(Number(warehouseId))) return;
    form.stock_balances.push({
        warehouse_id: Number(warehouseId),
        quantity: '0',
    });
}

function removeWarehouseRow(index: number) {
    form.stock_balances.splice(index, 1);
}

function selectWarehouseToAdd(value: unknown) {
    const id = Number(value);
    if (!id) return;
    addWarehouseRow(id);
    newWarehouseId.value = '';
    isAddingWarehouse.value = false;
}

function submit() {
    form.transform((data) => ({
        name_tr: data.name,
        name_en: data.name,
        category_id: data.category_id ? Number(data.category_id) : null,
        unit_id: Number(data.unit_id),
        unit_price: data.unit_price ? Number(data.unit_price) : null,
        stock_balances: (data.stock_balances ?? []).map((row: any) => ({
            warehouse_id: Number(row.warehouse_id),
            quantity: row.quantity === '' || row.quantity === null || row.quantity === undefined ? 0 : Number(row.quantity),
        })),
    })).put(update.url({ product: props.product.id }));
}
</script>

<template>
    <Head :title="t('products.editProduct')" />

    <AppLayout :breadcrumbs="breadcrumbs" :hide-sidebar="true">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <Card>
                <CardHeader>
                    <div class="flex items-center gap-3">
                        <div
                            class="rounded-lg bg-blue-100 p-2 dark:bg-blue-900"
                        >
                            <Package
                                class="h-6 w-6 text-blue-600 dark:text-blue-400"
                            />
                        </div>
                        <div>
                            <CardTitle>{{
                                t('products.editProduct')
                            }}</CardTitle>
                            <CardDescription>{{
                                t('products.title')
                            }}</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <form
                        @submit.prevent="submit"
                        class="grid gap-4 md:grid-cols-2"
                    >
                        <div class="space-y-2">
                            <Label for="name" class="flex items-center gap-2">
                                <Package
                                    class="h-4 w-4 text-muted-foreground"
                                />
                                {{ t('products.name') }} *
                            </Label>
                            <Input id="name" v-model="form.name" required />
                            <p
                                v-if="form.errors.name"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.name }}
                            </p>
                            <p
                                v-else-if="form.errors.name_tr"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.name_tr }}
                            </p>
                            <p
                                v-else-if="form.errors.name_en"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.name_en }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label
                                for="category_id"
                                class="flex items-center gap-2"
                            >
                                <Package
                                    class="h-4 w-4 text-muted-foreground"
                                />
                                {{ t('products.category') }}
                            </Label>
                            <SearchableSelect
                                :model-value="form.category_id"
                                :options="categoryOptions"
                                :placeholder="t('common.select')"
                                @update:model-value="
                                    (v) => (form.category_id = v)
                                "
                            />
                            <p
                                v-if="form.errors.category_id"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.category_id }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label
                                for="unit_id"
                                class="flex items-center gap-2"
                            >
                                <Package
                                    class="h-4 w-4 text-muted-foreground"
                                />
                                {{ t('products.unit') }} *
                            </Label>
                            <SearchableSelect
                                :model-value="form.unit_id"
                                :options="unitOptions"
                                :placeholder="t('common.select')"
                                @update:model-value="(v) => (form.unit_id = v)"
                            />
                            <p
                                v-if="form.errors.unit_id"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.unit_id }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label
                                for="unit_price"
                                class="flex items-center gap-2"
                            >
                                <Banknote
                                    class="h-4 w-4 text-muted-foreground"
                                />
                                {{ t('products.price') }}
                            </Label>
                            <Input
                                id="unit_price"
                                v-model="form.unit_price"
                                type="number"
                                step="any"
                            />
                            <p
                                v-if="form.errors.unit_price"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.unit_price }}
                            </p>
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <div class="rounded-lg border">
                                <div class="flex items-center justify-between gap-2 border-b p-3">
                                    <div class="text-sm font-medium">
                                        {{ t('products.stockByWarehouse') }}
                                    </div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="isAddingWarehouse = !isAddingWarehouse"
                                    >
                                        <Plus class="mr-2 h-4 w-4" />
                                        {{ t('products.addWarehouse') }}
                                    </Button>
                                </div>

                                <div v-if="isAddingWarehouse" class="border-b p-3">
                                    <Label class="mb-2 block text-sm">
                                        {{ t('stock.warehouse') }}
                                    </Label>
                                    <SearchableSelect
                                        :model-value="newWarehouseId"
                                        :options="availableWarehouseOptions"
                                        :placeholder="t('common.select')"
                                        @update:model-value="selectWarehouseToAdd"
                                    />
                                    <p
                                        v-if="availableWarehouseOptions.length === 0"
                                        class="mt-2 text-xs text-muted-foreground"
                                    >
                                        {{ t('products.allWarehousesAdded') }}
                                    </p>
                                </div>

                                <p
                                    v-if="form.errors.stock_balances"
                                    class="px-3 pt-3 text-sm text-destructive"
                                >
                                    {{ form.errors.stock_balances }}
                                </p>

                                <div
                                    class="grid grid-cols-1 gap-3 p-3 md:grid-cols-2"
                                >
                                    <p
                                        v-if="form.stock_balances.length === 0"
                                        class="text-sm text-muted-foreground md:col-span-2"
                                    >
                                        {{ t('products.noWarehousesSelected') }}
                                    </p>
                                    <div
                                        v-for="(row, idx) in form.stock_balances"
                                        :key="row.warehouse_id"
                                        class="flex items-center justify-between gap-3"
                                    >
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-medium">
                                                {{
                                                    warehouseOptions.find(
                                                        (w) =>
                                                            Number(w.id) ===
                                                            Number(
                                                                row.warehouse_id,
                                                            ),
                                                    )?.label ??
                                                    String(row.warehouse_id)
                                                }}
                                            </div>
                                            <p
                                                v-if="
                                                    form.errors[
                                                        `stock_balances.${idx}.quantity`
                                                    ]
                                                "
                                                class="text-xs text-destructive"
                                            >
                                                {{
                                                    form.errors[
                                                        `stock_balances.${idx}.quantity`
                                                    ]
                                                }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <Input
                                                :id="`stock_balances_${idx}_quantity`"
                                                v-model="
                                                    form.stock_balances[idx]
                                                        .quantity
                                                "
                                                type="number"
                                                step="any"
                                                class="w-32"
                                            />
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="icon"
                                                @click="removeWarehouseRow(idx)"
                                            >
                                                <X class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2 md:col-span-2">
                            <Button type="submit" :disabled="form.processing">
                                {{ t('common.save') }}
                            </Button>
                            <Link :href="index.url()">
                                <Button type="button" variant="outline">{{
                                    t('common.cancel')
                                }}</Button>
                            </Link>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
