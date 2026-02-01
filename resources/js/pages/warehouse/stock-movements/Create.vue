<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
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

const props = defineProps<{ type: string; warehouses: Array<Record<string, unknown>>; products: Array<Record<string, unknown>> }>();
const { t } = useI18n();
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.stockMovements'), href: index.url() }, { title: t('stockMovements.addMovement') }];
const locale = computed(() => (useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en'));
const form = useForm({
    warehouse_id: (props.warehouses[0] as any)?.id ?? '',
    product_id: '',
    type: props.type || 'in',
    quantity: '',
    unit_cost: '',
    from_warehouse_id: '',
    notes: '',
});
function submit() {
    form.transform((data) => ({
        ...data,
        warehouse_id: Number(data.warehouse_id),
        product_id: Number(data.product_id),
        quantity: Number(data.quantity),
        unit_cost: data.unit_cost ? Number(data.unit_cost) : null,
        from_warehouse_id: data.from_warehouse_id ? Number(data.from_warehouse_id) : null,
    })).post(store.url());
}
</script>

<template>
    <Head :title="t('stockMovements.addMovement')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="p-4 md:p-6 pb-0">
                    <h1 class="text-xl font-semibold">{{ form.type === 'in' ? t('nav.input') : form.type === 'out' ? t('nav.output') : t('stockMovements.addMovement') }}</h1>
                    <p class="text-sm text-muted-foreground">{{ t('stockMovements.title') }}</p>
                </div>
            </template>
            <div class="p-4 md:p-6 pt-4">
            <Card>
                <CardContent class="pt-6">
                    <form @submit.prevent="submit" class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2"><Label>{{ t('stockMovements.type') }}</Label><Input :model-value="form.type" disabled /></div>
                        <div class="space-y-2"><Label for="warehouse_id">{{ t('stock.warehouse') }}</Label>
                            <select id="warehouse_id" v-model="form.warehouse_id" required class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option value="">-</option>
                                <option v-for="w in warehouses" :key="(w as any).id" :value="(w as any).id">{{ (w as any)[locale] }}</option>
                            </select>
                        </div>
                        <div class="space-y-2"><Label for="product_id">{{ t('stock.product') }}</Label>
                            <select id="product_id" v-model="form.product_id" required class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option value="">-</option>
                                <option v-for="p in products" :key="(p as any).id" :value="(p as any).id">{{ (p as any)[locale] }}</option>
                            </select>
                        </div>
                        <div class="space-y-2"><Label for="quantity">{{ t('common.quantity') }}</Label><Input id="quantity" v-model="form.quantity" type="number" step="any" required /></div>
                        <div class="space-y-2"><Label for="movement_date">{{ t('common.date') }}</Label><Input id="movement_date" :value="nowTurkeyDatetimeLocal()" type="datetime-local" disabled class="bg-muted" /></div>
                        <div v-if="form.type === 'transfer'" class="space-y-2 md:col-span-2"><Label for="from_warehouse_id">{{ t('stockMovements.fromWarehouse') }}</Label>
                            <select id="from_warehouse_id" v-model="form.from_warehouse_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option value="">-</option>
                                <option v-for="w in warehouses" :key="(w as any).id" :value="(w as any).id">{{ (w as any)[locale] }}</option>
                            </select>
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
