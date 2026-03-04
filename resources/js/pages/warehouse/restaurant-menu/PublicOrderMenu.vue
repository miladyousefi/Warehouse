<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { BellRing, ShoppingBasket, UtensilsCrossed } from 'lucide-vue-next';
import { computed, reactive } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const { t } = useI18n();
const locale = computed(() => (useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en'));

const props = defineProps<{
  setting: { layout_type?: string } | null;
  table: { id: number; name: string | null; table_number: string | null; qr_token: string };
  categories: Array<Record<string, any>>;
}>();

const quantities = reactive<Record<number, number>>({});
const notes = reactive<Record<number, string>>({});
const orderForm = useForm({ customer_note: '', items: [] as Array<{ id: number; quantity: number; note?: string }> });
const waiterForm = useForm({ note: '' });
const confirmOrderOpen = reactive({ value: false });

function getQty(id: number): number {
  return quantities[id] ?? 0;
}

function addItem(id: number): void {
  quantities[id] = getQty(id) + 1;
}

function removeItem(id: number): void {
  quantities[id] = Math.max(getQty(id) - 1, 0);
}

const cartItems = computed(() => {
  const byId = new Map<number, Record<string, any>>();
  for (const cat of props.categories) {
    for (const item of cat.items || []) byId.set(item.id, item);
  }

  return Object.entries(quantities)
    .map(([id, qty]) => ({ id: Number(id), quantity: qty, item: byId.get(Number(id)) }))
    .filter((x) => x.quantity > 0 && x.item);
});

const total = computed(() => cartItems.value.reduce((sum, x) => sum + Number(x.item.sale_price) * x.quantity, 0));

function placeOrder(): void {
  orderForm.items = cartItems.value.map((x) => ({
    id: x.id,
    quantity: x.quantity,
    note: notes[x.id] || undefined,
  }));

  if (orderForm.items.length === 0) return;
  confirmOrderOpen.value = true;
}

function submitConfirmedOrder(): void {
  confirmOrderOpen.value = false;

  orderForm.post(route('restaurant-order.store', { token: props.table.qr_token }), {
    preserveScroll: true,
    onSuccess: () => {
      for (const key of Object.keys(quantities)) quantities[Number(key)] = 0;
      orderForm.customer_note = '';
      orderForm.items = [];
    },
  });
}

function callWaiter(): void {
  waiterForm.post(route('restaurant-order.call-waiter', { token: props.table.qr_token }), {
    preserveScroll: true,
    onSuccess: () => {
      waiterForm.note = '';
    },
  });
}
</script>

<template>
  <Head :title="`${t('restaurantMenu.title')} - ${table.name || table.table_number}`" />

  <div class="min-h-screen bg-slate-50 text-slate-900">
    <div class="mx-auto max-w-7xl px-4 py-8 md:px-6">
      <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm md:p-8">
        <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600"><UtensilsCrossed class="h-3.5 w-3.5" /> {{ t('restaurantMenu.publicMenu') }}</div>
        <h1 class="mt-2 font-serif text-3xl font-black tracking-tight md:text-4xl">{{ t('restaurantMenu.title') }}</h1>
        <p class="mt-2 text-sm text-slate-600">{{ t('restaurantMenu.tableLabel') }}: {{ table.name || table.table_number }}</p>
      </section>

      <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_360px]">
        <section class="space-y-6">
          <article v-for="(cat, catIndex) in categories" :key="cat.id" class="menu-fade space-y-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" :style="{ animationDelay: `${catIndex * 50}ms` }">
            <div class="flex items-center gap-3">
              <img v-if="cat.image_url" :src="cat.image_url" class="h-10 w-10 rounded-lg object-cover" alt="cat" />
              <div v-else class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-xl">{{ cat.icon || '🍽️' }}</div>
              <h2 class="font-serif text-xl font-bold">{{ cat[locale] || cat.name_tr }}</h2>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
              <article v-for="item in cat.items" :key="item.id" class="menu-card rounded-xl border border-slate-200 bg-slate-50 p-3 transition hover:border-slate-300">
                <div class="flex gap-3">
                  <img v-if="item.image_url" :src="item.image_url" class="h-16 w-16 rounded-lg object-cover" alt="food" />
                  <div class="min-w-0 flex-1">
                    <div class="flex justify-between gap-2">
                      <h3 class="truncate font-semibold">{{ item[locale] || item.name_tr }}</h3>
                      <strong class="shrink-0 rounded-full border border-slate-300 bg-white px-2 py-0.5">{{ Number(item.sale_price).toFixed(2) }}</strong>
                    </div>
                    <p v-if="item[locale === 'name_tr' ? 'description_tr' : 'description_en']" class="line-clamp-2 text-xs text-slate-600">
                      {{ item[locale === 'name_tr' ? 'description_tr' : 'description_en'] }}
                    </p>
                    <div class="mt-2 flex items-center gap-2">
                      <Button size="sm" variant="outline" @click="removeItem(item.id)">-</Button>
                      <span class="w-8 text-center">{{ getQty(item.id) }}</span>
                      <Button size="sm" @click="addItem(item.id)">+</Button>
                    </div>
                    <Input v-if="getQty(item.id) > 0" v-model="notes[item.id]" class="mt-2" :placeholder="t('restaurantMenu.itemNote')" />
                  </div>
                </div>
              </article>
            </div>
          </article>
        </section>

        <aside class="space-y-4 xl:sticky xl:top-4 xl:self-start">
          <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="flex items-center gap-2 text-lg font-semibold"><ShoppingBasket class="h-4 w-4" />{{ t('restaurantMenu.yourOrder') }}</h3>
            <div v-if="cartItems.length === 0" class="mt-2 text-sm text-slate-500">{{ t('restaurantMenu.emptyCart') }}</div>
            <ul v-else class="mt-2 space-y-2 text-sm">
              <li v-for="line in cartItems" :key="line.id" class="flex justify-between gap-2">
                <span class="truncate">{{ line.item[locale] || line.item.name_tr }} x{{ line.quantity }}</span>
                <span class="shrink-0">{{ (Number(line.item.sale_price) * line.quantity).toFixed(2) }}</span>
              </li>
            </ul>
            <div class="mt-3 border-t border-slate-200 pt-3 text-lg font-bold">{{ t('common.total') }}: {{ total.toFixed(2) }}</div>
            <p class="mt-1 text-xs text-slate-500">{{ t('restaurantMenu.unpaidInfo') }}</p>
            <Input v-model="orderForm.customer_note" class="mt-3" :placeholder="t('restaurantMenu.customerNote')" />
            <Button class="mt-3 w-full" :disabled="orderForm.processing || cartItems.length === 0" @click="placeOrder">{{ t('restaurantMenu.placeOrder') }}</Button>
          </section>

          <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="flex items-center gap-2 text-lg font-semibold"><BellRing class="h-4 w-4" />{{ t('restaurantMenu.callWaiter') }}</h3>
            <Input v-model="waiterForm.note" class="mt-2" :placeholder="t('restaurantMenu.waiterNote')" />
            <Button class="mt-3 w-full" variant="outline" :disabled="waiterForm.processing" @click="callWaiter">{{ t('restaurantMenu.callWaiterButton') }}</Button>
          </section>
        </aside>
      </div>
    </div>

    <div v-if="confirmOrderOpen.value" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-5 shadow-lg">
        <h3 class="text-lg font-semibold">{{ t('restaurantMenu.confirmPlaceOrderTitle') }}</h3>
        <p class="mt-2 text-sm text-slate-600">
          {{ t('restaurantMenu.confirmPlaceOrderText') }}
        </p>
        <div class="mt-4 flex justify-end gap-2">
          <Button variant="outline" size="sm" @click="confirmOrderOpen.value = false">{{ t('common.cancel') }}</Button>
          <Button size="sm" :disabled="orderForm.processing" @click="submitConfirmedOrder">{{ t('restaurantMenu.confirmPlaceOrderButton') }}</Button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.menu-fade {
  animation: menu-fade-in 380ms ease both;
}

.menu-card {
  transition: transform 200ms ease, box-shadow 200ms ease;
}

.menu-card:hover {
  transform: translateY(-2px);
}

@keyframes menu-fade-in {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
