<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import { useI18n } from 'vue-i18n';
import AppPageContent from '@/components/AppPageContent.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const { t } = useI18n();
const locale = computed(() => (useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en'));

const props = defineProps<{
  tables: Array<Record<string, any>>;
  categories: Array<Record<string, any>>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: t('nav.restaurantOrders'), href: '/warehouse/restaurant-orders' },
  { title: t('restaurantMenu.manualOrder') },
];

const quantities = reactive<Record<number, number>>({});
const notes = reactive<Record<number, string>>({});
const form = useForm({
  restaurant_table_id: '',
  customer_note: '',
  items: [] as Array<{ id: number; quantity: number; note?: string }>,
});

function qty(id: number): number {
  return quantities[id] ?? 0;
}

function add(id: number): void {
  quantities[id] = qty(id) + 1;
}

function remove(id: number): void {
  quantities[id] = Math.max(qty(id) - 1, 0);
}

const selectedItems = computed(() => {
  const itemMap = new Map<number, Record<string, any>>();
  for (const cat of props.categories) {
    for (const item of cat.items || []) {
      itemMap.set(item.id, item);
    }
  }

  return Object.entries(quantities)
    .map(([id, quantity]) => ({
      id: Number(id),
      quantity,
      item: itemMap.get(Number(id)),
      note: notes[Number(id)] || '',
    }))
    .filter((line) => line.quantity > 0 && line.item);
});

const total = computed(() => selectedItems.value.reduce((sum, line) => sum + Number(line.item.sale_price) * line.quantity, 0));

function submit(): void {
  form.items = selectedItems.value.map((line) => ({
    id: line.id,
    quantity: line.quantity,
    note: line.note || undefined,
  }));

  form.post(route('warehouse.restaurant-orders.manual.store'), {
    preserveScroll: true,
  });
}
</script>

<template>
  <Head :title="t('restaurantMenu.manualOrder')" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <AppPageContent>
      <template #header>
        <div class="p-4 pb-0 md:p-6 md:pb-0">
          <h1 class="text-xl font-semibold">{{ t('restaurantMenu.manualOrder') }}</h1>
          <p class="text-sm text-muted-foreground">{{ t('restaurantMenu.manualOrderHelp') }}</p>
        </div>
      </template>
      <div class="p-4 pt-4 md:p-6 md:pt-4">
        <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
          <Card>
            <CardHeader><CardTitle>{{ t('restaurantMenu.selectItems') }}</CardTitle></CardHeader>
            <CardContent class="space-y-5">
              <div class="space-y-2">
                <Label>{{ t('restaurantMenu.tableLabel') }}</Label>
                <select v-model="form.restaurant_table_id" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                  <option value="">{{ t('common.select') }}</option>
                  <option v-for="table in tables" :key="table.id" :value="table.id">
                    {{ table.name || table.table_number }} ({{ table.table_number }})
                  </option>
                </select>
                <p v-if="form.errors.restaurant_table_id" class="text-xs text-destructive">{{ form.errors.restaurant_table_id }}</p>
              </div>

              <article v-for="cat in categories" :key="cat.id" class="space-y-3 rounded-md border bg-muted/20 p-4">
                <h2 class="text-lg font-semibold">{{ cat[locale] || cat.name_tr }}</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                  <div v-for="item in cat.items" :key="item.id" class="rounded-md border bg-background p-3">
                    <div class="flex gap-3">
                      <img v-if="item.image_url" :src="item.image_url" class="h-16 w-16 rounded object-cover" alt="food" />
                      <div class="min-w-0 flex-1">
                        <div class="flex justify-between gap-2">
                          <h3 class="truncate font-medium">{{ item[locale] || item.name_tr }}</h3>
                          <strong>{{ Number(item.sale_price).toFixed(2) }}</strong>
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                          <Button size="sm" variant="outline" @click="remove(item.id)">-</Button>
                          <span class="w-8 text-center">{{ qty(item.id) }}</span>
                          <Button size="sm" @click="add(item.id)">+</Button>
                        </div>
                        <Input v-if="qty(item.id) > 0" v-model="notes[item.id]" class="mt-2" :placeholder="t('restaurantMenu.itemNote')" />
                      </div>
                    </div>
                  </div>
                </div>
              </article>
            </CardContent>
          </Card>

          <aside class="space-y-4 xl:sticky xl:top-4 xl:self-start">
            <Card>
              <CardHeader><CardTitle>{{ t('restaurantMenu.orderSummary') }}</CardTitle></CardHeader>
              <CardContent>
                <div v-if="selectedItems.length === 0" class="text-sm text-muted-foreground">{{ t('restaurantMenu.emptyCart') }}</div>
                <ul v-else class="space-y-2 text-sm">
                  <li v-for="line in selectedItems" :key="line.id" class="flex justify-between gap-2">
                    <span class="truncate">{{ line.item[locale] || line.item.name_tr }} x{{ line.quantity }}</span>
                    <span class="shrink-0">{{ (Number(line.item.sale_price) * line.quantity).toFixed(2) }}</span>
                  </li>
                </ul>
                <div class="mt-3 border-t pt-3 text-lg font-bold">{{ t('common.total') }}: {{ total.toFixed(2) }}</div>
                <Input v-model="form.customer_note" class="mt-3" :placeholder="t('restaurantMenu.customerNote')" />
                <p v-if="form.errors.items" class="mt-2 text-xs text-destructive">{{ form.errors.items }}</p>
                <div class="mt-3 flex gap-2">
                  <Button class="flex-1" :disabled="form.processing || selectedItems.length === 0" @click="submit">{{ t('restaurantMenu.submitManualOrder') }}</Button>
                  <Link href="/warehouse/restaurant-orders" class="flex-1"><Button variant="outline" class="w-full">{{ t('common.cancel') }}</Button></Link>
                </div>
              </CardContent>
            </Card>
          </aside>
        </div>
      </div>
    </AppPageContent>
  </AppLayout>
</template>
