<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { index, update } from '@/actions/App/Http/Controllers/Warehouse/ProductController';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

const { t } = useI18n();

const props = defineProps<{
    product: Record<string, unknown>;
    categories: Array<Record<string, unknown>>;
    units: Array<Record<string, unknown>>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('nav.products'), href: index.url() },
    { title: t('products.editProduct') },
];

const form = useForm({
    category_id: (props.product.category_id as number) ?? null,
    unit_id: String(props.product.unit_id ?? ''),
    name_tr: String(props.product.name_tr ?? ''),
    name_en: String(props.product.name_en ?? ''),
    sku: String(props.product.sku ?? ''),
    barcode: String(props.product.barcode ?? ''),
    description_tr: String(props.product.description_tr ?? ''),
    description_en: String(props.product.description_en ?? ''),
    min_stock: String(props.product.min_stock ?? ''),
    max_stock: String(props.product.max_stock ?? ''),
    cost_price: String(props.product.cost_price ?? ''),
    selling_price: String(props.product.selling_price ?? ''),
    is_active: Boolean(props.product.is_active ?? true),
    track_quantity: Boolean(props.product.track_quantity ?? true),
});

function submit() {
    form.transform((data) => ({
        ...data,
        unit_id: Number(data.unit_id),
        category_id: data.category_id || undefined,
        min_stock: data.min_stock ? Number(data.min_stock) : null,
        max_stock: data.max_stock ? Number(data.max_stock) : null,
        cost_price: data.cost_price ? Number(data.cost_price) : null,
        selling_price: data.selling_price ? Number(data.selling_price) : null,
    })).put(`/warehouse/products/${props.product.id}`);
}
</script>

<template>
    <Head :title="t('products.editProduct')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('products.editProduct') }}</CardTitle>
                    <CardDescription>{{ t('products.title') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="name_tr">{{ t('products.nameTr') }}</Label>
                            <Input id="name_tr" v-model="form.name_tr" required />
                            <p v-if="form.errors.name_tr" class="text-sm text-destructive">
                                {{ form.errors.name_tr }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label for="name_en">{{ t('products.nameEn') }}</Label>
                            <Input id="name_en" v-model="form.name_en" required />
                            <p v-if="form.errors.name_en" class="text-sm text-destructive">
                                {{ form.errors.name_en }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label for="sku">{{ t('products.sku') }}</Label>
                            <Input id="sku" v-model="form.sku" />
                        </div>
                        <div class="space-y-2">
                            <Label for="barcode">{{ t('products.barcode') }}</Label>
                            <Input id="barcode" v-model="form.barcode" />
                        </div>
                        <div class="space-y-2">
                            <Label for="category_id">{{ t('products.category') }}</Label>
                            <select
                                id="category_id"
                                v-model="form.category_id"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option :value="null">-</option>
                                <option
                                    v-for="c in categories"
                                    :key="(c as any).id"
                                    :value="(c as any).id"
                                >
                                    {{ (c as any).name_tr }}
                                </option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="unit_id">{{ t('products.unit') }} *</Label>
                            <select
                                id="unit_id"
                                v-model="form.unit_id"
                                required
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="">-</option>
                                <option
                                    v-for="u in units"
                                    :key="(u as any).id"
                                    :value="(u as any).id"
                                >
                                    {{ (u as any).symbol }} ({{ (u as any).name_tr }})
                                </option>
                            </select>
                            <p v-if="form.errors.unit_id" class="text-sm text-destructive">
                                {{ form.errors.unit_id }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label for="min_stock">{{ t('products.minStock') }}</Label>
                            <Input id="min_stock" v-model="form.min_stock" type="number" step="any" />
                        </div>
                        <div class="space-y-2">
                            <Label for="max_stock">{{ t('products.maxStock') }}</Label>
                            <Input id="max_stock" v-model="form.max_stock" type="number" step="any" />
                        </div>
                        <div class="space-y-2">
                            <Label for="cost_price">{{ t('products.costPrice') }}</Label>
                            <Input id="cost_price" v-model="form.cost_price" type="number" step="any" />
                        </div>
                        <div class="space-y-2">
                            <Label for="selling_price">{{ t('products.sellingPrice') }}</Label>
                            <Input id="selling_price" v-model="form.selling_price" type="number" step="any" />
                        </div>
                        <div class="flex items-center space-x-2 md:col-span-2">
                            <Checkbox
                                id="is_active"
                                :checked="form.is_active"
                                @update:checked="(v: boolean) => (form.is_active = v)"
                            />
                            <Label for="is_active">{{ t('common.active') }}</Label>
                        </div>
                        <div class="flex items-center space-x-2 md:col-span-2">
                            <Checkbox
                                id="track_quantity"
                                :checked="form.track_quantity"
                                @update:checked="(v: boolean) => (form.track_quantity = v)"
                            />
                            <Label for="track_quantity">{{ t('products.trackQuantity') }}</Label>
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
