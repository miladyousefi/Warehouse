<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { index, update } from '@/actions/App/Http/Controllers/Warehouse/ProductController';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import SearchableSelect from '@/components/SearchableSelect.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Package, Banknote, Warehouse } from 'lucide-vue-next';

const { t } = useI18n();
const locale = computed(() => (useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en'));

const props = defineProps<{
    product: Record<string, unknown>;
    warehouses?: Array<Record<string, unknown>>;
    units?: Array<Record<string, unknown>>;
    categories?: Array<Record<string, unknown>>;
}>();

const page = usePage();
const pageWarehouses = ((page.props as any).warehouses as Array<Record<string, unknown>> | undefined) ?? [];
const pageUnits = ((page.props as any).units as Array<Record<string, unknown>> | undefined) ?? [];
const pageCategories = ((page.props as any).categories as Array<Record<string, unknown>> | undefined) ?? [];
const warehouses = (props.warehouses as Array<Record<string, unknown>> | undefined) ?? pageWarehouses;
const units = (props.units as Array<Record<string, unknown>> | undefined) ?? pageUnits;
const categories = (props.categories as Array<Record<string, unknown>> | undefined) ?? pageCategories;

const categoryOptions = computed(() =>
    categories.map(c => ({
        id: (c as any).id,
        label: (c as any)[locale.value],
    }))
);

const unitOptions = computed(() =>
    units.map(u => ({
        id: (u as any).id,
        label: (u as any)[locale.value],
    }))
);

const warehouseOptions = computed(() =>
    warehouses.map(w => ({
        id: (w as any).id,
        label: (w as any)[locale.value],
    }))
);

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('nav.products'), href: index.url() },
    { title: t('products.editProduct') },
];

const form = useForm({
    name: String(props.product.name_en ?? ''),
    category_id: (props.product as any).category_id ?? '',
    unit_id: (props.product as any).unit_id ?? (units[0] as any)?.id ?? '',
    unit_price: String(props.product.unit_price ?? ''),
    warehouse_id: (props.product as any).warehouse_id ?? (warehouses[0] as any)?.id ?? '',
    initial_stock: '',
});

function submit() {
    form.transform((data) => ({
        name_tr: data.name,
        name_en: data.name,
        category_id: data.category_id ? Number(data.category_id) : null,
        unit_id: Number(data.unit_id),
        unit_price: data.unit_price ? Number(data.unit_price) : null,
        warehouse_id: Number(data.warehouse_id),
        initial_stock: data.initial_stock ? Number(data.initial_stock) : undefined,
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
                        <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900">
                            <Package class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <CardTitle>{{ t('products.editProduct') }}</CardTitle>
                            <CardDescription>{{ t('products.title') }}</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="name" class="flex items-center gap-2">
                                <Package class="h-4 w-4 text-muted-foreground" />
                                {{ t('products.name') }} *
                            </Label>
                            <Input id="name" v-model="form.name" required />
                            <p v-if="form.errors.name" class="text-sm text-destructive">
                                {{ form.errors.name }}
                            </p>
                            <p v-else-if="form.errors.name_tr" class="text-sm text-destructive">
                                {{ form.errors.name_tr }}
                            </p>
                            <p v-else-if="form.errors.name_en" class="text-sm text-destructive">
                                {{ form.errors.name_en }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label for="category_id" class="flex items-center gap-2">
                                <Package class="h-4 w-4 text-muted-foreground" />
                                {{ t('products.category') }}
                            </Label>
                            <SearchableSelect :model-value="form.category_id" :options="categoryOptions" :placeholder="t('common.select')" @update:model-value="(v) => form.category_id = v" />
                            <p v-if="form.errors.category_id" class="text-sm text-destructive">
                                {{ form.errors.category_id }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label for="unit_id" class="flex items-center gap-2">
                                <Package class="h-4 w-4 text-muted-foreground" />
                                {{ t('products.unit') }} *
                            </Label>
                            <SearchableSelect :model-value="form.unit_id" :options="unitOptions" :placeholder="t('common.select')" @update:model-value="(v) => form.unit_id = v" />
                            <p v-if="form.errors.unit_id" class="text-sm text-destructive">
                                {{ form.errors.unit_id }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label for="unit_price" class="flex items-center gap-2">
                                <Banknote class="h-4 w-4 text-muted-foreground" />
                                {{ t('products.price') }}
                            </Label>
                            <Input id="unit_price" v-model="form.unit_price" type="number" step="any" />
                            <p v-if="form.errors.unit_price" class="text-sm text-destructive">
                                {{ form.errors.unit_price }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label for="initial_stock" class="flex items-center gap-2">
                                <Warehouse class="h-4 w-4 text-muted-foreground" />
                                {{ t('common.quantity') }}
                            </Label>
                            <Input id="initial_stock" v-model="form.initial_stock" type="number" step="any" />
                            <p v-if="form.errors.initial_stock" class="text-sm text-destructive">
                                {{ form.errors.initial_stock }}
                            </p>
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <Label for="warehouse_id" class="flex items-center gap-2">
                                <Warehouse class="h-4 w-4 text-muted-foreground" />
                                {{ t('stock.warehouse') }} *
                            </Label>
                            <SearchableSelect :model-value="form.warehouse_id" :options="warehouseOptions" :placeholder="t('common.select')" @update:model-value="(v) => form.warehouse_id = v" />
                            <p v-if="form.errors.warehouse_id" class="text-sm text-destructive">
                                {{ form.errors.warehouse_id }}
                            </p>
                        </div>
                        <div class="flex gap-2 md:col-span-2">
                            <Button type="submit" :disabled="form.processing">
                                {{ t('common.save') }}
                            </Button>
                            <Link :href="index.url()">
                                <Button type="button" variant="outline">{{ t('common.cancel') }}</Button>
                            </Link>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
