<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const { t } = useI18n();
const locale = computed(() => (useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en'));

const props = defineProps<{ item: Record<string, any>; categories: Array<Record<string, any>>; products: Array<Record<string, any>> }>();
const breadcrumbs: BreadcrumbItem[] = [
  { title: t('nav.restaurantMenu'), href: '/warehouse/restaurant-menu' },
  { title: t('common.edit') },
];

const form = useForm({
  restaurant_menu_category_id: props.item.restaurant_menu_category_id ? String(props.item.restaurant_menu_category_id) : '',
  name_tr: String(props.item.name_tr ?? ''),
  name_en: String(props.item.name_en ?? ''),
  description_tr: String(props.item.description_tr ?? ''),
  description_en: String(props.item.description_en ?? ''),
  images: [] as File[],
  cover_image_key: '',
  remove_image: false,
  sale_price: String(props.item.sale_price ?? ''),
  is_active: Boolean(props.item.is_active ?? true),
  sort_order: Number(props.item.sort_order ?? 0),
  ingredients: ((props.item.ingredients as Array<Record<string, any>> | undefined) ?? []).map((ing) => ({
    product_id: String(ing.product_id ?? ''),
    quantity: String(ing.quantity ?? ''),
  })),
});

if (form.ingredients.length === 0) {
  form.ingredients.push({ product_id: '', quantity: '' });
}

const existingUrls = ((props.item.image_gallery_urls as string[] | undefined) ?? []).length > 0
  ? ((props.item.image_gallery_urls as string[] | undefined) ?? [])
  : (props.item.image_url ? [String(props.item.image_url)] : []);

const existingPreviews = existingUrls.map((url, idx) => ({
  key: `old:${idx}`,
  url,
}));

const newPreviews = ref<Array<{ key: string; url: string }>>([]);
if (existingPreviews.length > 0) {
  form.cover_image_key = existingPreviews[0].key;
}

function addIngredient() { form.ingredients.push({ product_id: '', quantity: '' }); }
function removeIngredient(idx: number) { if (form.ingredients.length > 1) form.ingredients.splice(idx, 1); }
function submit() { form.patch(`/warehouse/restaurant-menu/${props.item.id}`, { forceFormData: true }); }

function setImages(event: Event) {
  const target = event.target as HTMLInputElement;
  const files = Array.from(target.files ?? []);
  if (files.length === 0) return;

  const baseIndex = form.images.length;
  form.images.push(...files);

  files.forEach((file, idx) => {
    newPreviews.value.push({
      key: `new:${baseIndex + idx}`,
      url: URL.createObjectURL(file),
    });
  });

  if (!form.cover_image_key && (existingPreviews.length > 0 || newPreviews.value.length > 0)) {
    form.cover_image_key = existingPreviews[0]?.key || newPreviews.value[0].key;
  }
}
</script>

<template>
  <Head :title="t('restaurantMenu.editFood')" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
      <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <h1 class="text-2xl font-black tracking-tight text-slate-900 md:text-3xl">{{ t('restaurantMenu.editFood') }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ t('restaurantMenu.menuSubtitle') }}</p>
      </section>

      <form class="grid gap-4 xl:grid-cols-[1fr_340px]" @submit.prevent="submit">
        <Card>
          <CardHeader><CardTitle>{{ t('restaurantMenu.editFood') }}</CardTitle></CardHeader>
          <CardContent class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
              <Label>{{ t('restaurantMenu.category') }}</Label>
              <select v-model="form.restaurant_menu_category_id" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                <option value="">-</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c[locale] || c.name_tr }}</option>
              </select>
            </div>
            <div class="space-y-2"><Label>{{ t('restaurantMenu.salePrice') }}</Label><Input v-model="form.sale_price" type="number" step="0.01" required /></div>
            <div class="space-y-2"><Label>{{ t('restaurantMenu.nameTr') }}</Label><Input v-model="form.name_tr" required /></div>
            <div class="space-y-2"><Label>{{ t('restaurantMenu.nameEn') }}</Label><Input v-model="form.name_en" required /></div>
            <div class="space-y-2 md:col-span-2"><Label>{{ t('restaurantMenu.descriptionTr') }}</Label><Input v-model="form.description_tr" /></div>
            <div class="space-y-2 md:col-span-2"><Label>{{ t('restaurantMenu.descriptionEn') }}</Label><Input v-model="form.description_en" /></div>

            <div class="space-y-2 md:col-span-2">
              <Label>{{ t('restaurantMenu.foodImages') }}</Label>
              <Input type="file" accept="image/*" multiple @change="setImages" />
              <div class="grid gap-3 md:grid-cols-4">
                <label v-for="img in existingPreviews" :key="img.key" class="rounded-xl border border-slate-200 bg-white p-2 text-xs shadow-sm">
                  <img :src="img.url" alt="food" class="mb-2 h-28 w-full rounded-lg object-cover" />
                  <div class="flex items-center gap-2 text-slate-700">
                    <input v-model="form.cover_image_key" :value="img.key" type="radio" name="cover_edit" />
                    {{ t('restaurantMenu.selectCover') }}
                  </div>
                </label>
                <label v-for="img in newPreviews" :key="img.key" class="rounded-xl border border-slate-200 bg-white p-2 text-xs shadow-sm">
                  <img :src="img.url" alt="food" class="mb-2 h-28 w-full rounded-lg object-cover" />
                  <div class="flex items-center gap-2 text-slate-700">
                    <input v-model="form.cover_image_key" :value="img.key" type="radio" name="cover_edit" />
                    {{ t('restaurantMenu.selectCover') }}
                  </div>
                </label>
              </div>
              <label class="flex items-center gap-2 text-sm"><input v-model="form.remove_image" type="checkbox" />{{ t('restaurantMenu.removeFoodImage') }}</label>
            </div>

            <div class="space-y-3 md:col-span-2">
              <div class="flex items-center justify-between">
                <Label>{{ t('restaurantMenu.ingredients') }}</Label>
                <Button type="button" variant="outline" size="sm" @click="addIngredient">{{ t('common.add') }}</Button>
              </div>
              <div v-for="(ing, idx) in form.ingredients" :key="idx" class="grid gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 md:grid-cols-3">
                <select v-model="ing.product_id" class="w-full rounded-md border border-input bg-white px-3 py-2 text-sm">
                  <option value="">{{ t('restaurantMenu.rawMaterial') }}</option>
                  <option v-for="p in products" :key="p.id" :value="p.id">{{ p[locale] || p.name_tr }}</option>
                </select>
                <Input v-model="ing.quantity" type="number" step="0.0001" :placeholder="t('common.quantity')" />
                <Button type="button" variant="destructive" @click="removeIngredient(idx)">{{ t('common.delete') }}</Button>
              </div>
            </div>
          </CardContent>
        </Card>

        <div class="space-y-4 xl:sticky xl:top-4 xl:self-start">
          <Card>
            <CardHeader><CardTitle>{{ t('common.actions') }}</CardTitle></CardHeader>
            <CardContent class="space-y-2">
              <Button type="submit" class="w-full" :disabled="form.processing">{{ t('common.save') }}</Button>
              <Link href="/warehouse/restaurant-menu" class="block"><Button type="button" variant="outline" class="w-full">{{ t('common.cancel') }}</Button></Link>
            </CardContent>
          </Card>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
