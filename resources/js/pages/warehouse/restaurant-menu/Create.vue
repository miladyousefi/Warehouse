<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Upload, X } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const { t } = useI18n();
const locale = computed(() =>
    useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en',
);

defineProps<{
    categories: Array<Record<string, any>>;
    products: Array<Record<string, any>>;
}>();
const breadcrumbs: BreadcrumbItem[] = [
    { title: t('nav.restaurantMenu'), href: '/warehouse/restaurant-menu' },
    { title: t('restaurantMenu.addFood') },
];

const form = useForm({
    restaurant_menu_category_id: '',
    name_tr: '',
    name_en: '',
    description_tr: '',
    description_en: '',
    images: [] as File[],
    cover_image_key: '',
    sale_price: '',
    is_active: true,
    sort_order: 0,
    ingredients: [{ product_id: '', quantity: '' }],
});

const fileInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const imagePreviews = ref<Array<{ key: string; url: string; file: File }>>([]);

function addIngredient() {
    form.ingredients.push({ product_id: '', quantity: '' });
}
function removeIngredient(idx: number) {
    if (form.ingredients.length > 1) form.ingredients.splice(idx, 1);
}

function syncImagesToForm() {
    form.images = imagePreviews.value.map((x) => x.file);

    const hasSelectedCover = imagePreviews.value.some(
        (x) => x.key === form.cover_image_key,
    );
    if (!hasSelectedCover) {
        form.cover_image_key = imagePreviews.value[0]?.key || '';
    }
}

function addFiles(files: File[]) {
    if (files.length === 0) return;

    const accepted = files.filter((f) => f.type.startsWith('image/'));
    if (accepted.length === 0) return;

    const base = imagePreviews.value.length;
    accepted.forEach((file, idx) => {
        imagePreviews.value.push({
            key: `new:${base + idx}`,
            url: URL.createObjectURL(file),
            file,
        });
    });

    syncImagesToForm();
}

function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    addFiles(Array.from(target.files ?? []));
    target.value = '';
}

function openFilePicker() {
    fileInput.value?.click();
}

function onDrop(event: DragEvent) {
    isDragging.value = false;
    event.preventDefault();
    addFiles(Array.from(event.dataTransfer?.files ?? []));
}

function removeImage(index: number) {
    const target = imagePreviews.value[index];
    if (!target) return;
    URL.revokeObjectURL(target.url);
    imagePreviews.value.splice(index, 1);
    imagePreviews.value = imagePreviews.value.map((img, idx) => ({
        ...img,
        key: `new:${idx}`,
    }));
    syncImagesToForm();
}

function submit() {
    syncImagesToForm();
    form.post('/warehouse/restaurant-menu', { forceFormData: true });
}

onBeforeUnmount(() => {
    imagePreviews.value.forEach((img) => URL.revokeObjectURL(img.url));
});
</script>

<template>
    <Head :title="t('restaurantMenu.addFood')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <section
                class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <h1
                    class="text-2xl font-black tracking-tight text-slate-900 md:text-3xl"
                >
                    {{ t('restaurantMenu.addFood') }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ t('restaurantMenu.menuSubtitle') }}
                </p>
            </section>

            <form
                class="grid gap-4 xl:grid-cols-[1fr_340px]"
                @submit.prevent="submit"
            >
                <Card>
                    <CardHeader
                        ><CardTitle>{{
                            t('restaurantMenu.addFood')
                        }}</CardTitle></CardHeader
                    >
                    <CardContent class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label>{{ t('restaurantMenu.category') }}</Label>
                            <select
                                v-model="form.restaurant_menu_category_id"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="">-</option>
                                <option
                                    v-for="c in categories"
                                    :key="c.id"
                                    :value="c.id"
                                >
                                    {{ c[locale] || c.name_tr }}
                                </option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label>{{ t('restaurantMenu.salePrice') }}</Label
                            ><Input
                                v-model="form.sale_price"
                                type="number"
                                step="0.01"
                                required
                            />
                        </div>
                        <div class="space-y-2">
                            <Label>{{ t('restaurantMenu.nameTr') }}</Label
                            ><Input v-model="form.name_tr" required />
                        </div>
                        <div class="space-y-2">
                            <Label>{{ t('restaurantMenu.nameEn') }}</Label
                            ><Input v-model="form.name_en" required />
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <Label>{{
                                t('restaurantMenu.descriptionTr')
                            }}</Label
                            ><Input v-model="form.description_tr" />
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <Label>{{
                                t('restaurantMenu.descriptionEn')
                            }}</Label
                            ><Input v-model="form.description_en" />
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <Label>{{ t('restaurantMenu.foodImages') }}</Label>
                            <input
                                ref="fileInput"
                                type="file"
                                accept="image/*"
                                multiple
                                class="hidden"
                                @change="onFileChange"
                            />
                            <div
                                class="rounded-2xl border-2 border-dashed p-7 text-center transition"
                                :class="
                                    isDragging
                                        ? 'border-primary bg-primary/5'
                                        : 'border-slate-300 bg-slate-50'
                                "
                                @click="openFilePicker"
                                @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop="onDrop"
                            >
                                <Upload
                                    class="mx-auto mb-2 h-7 w-7 text-slate-500"
                                />
                                <p class="text-sm font-semibold text-slate-800">
                                    {{
                                        t('restaurantMenu.dragDropImages') ||
                                        'Drag images here or click to upload'
                                    }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    JPG, PNG, WEBP
                                </p>
                            </div>
                            <p
                                v-if="form.errors.images"
                                class="text-xs text-destructive"
                            >
                                {{ form.errors.images }}
                            </p>
                            <p
                                v-if="(form.errors as any)['images.0']"
                                class="text-xs text-destructive"
                            >
                                {{ (form.errors as any)['images.0'] }}
                            </p>

                            <div class="grid gap-3 md:grid-cols-4">
                                <div
                                    v-for="(img, index) in imagePreviews"
                                    :key="img.key"
                                    class="rounded-xl border border-slate-200 bg-white p-2 text-xs shadow-sm"
                                >
                                    <img
                                        :src="img.url"
                                        alt="food"
                                        class="mb-2 h-28 w-full rounded-lg object-cover"
                                    />
                                    <div
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <label
                                            class="flex items-center gap-2 text-slate-700"
                                        >
                                            <input
                                                v-model="form.cover_image_key"
                                                :value="img.key"
                                                type="radio"
                                                name="cover_create"
                                            />
                                            {{
                                                t('restaurantMenu.selectCover')
                                            }}
                                        </label>
                                        <button
                                            type="button"
                                            class="rounded p-1 hover:bg-slate-100"
                                            @click="removeImage(index)"
                                        >
                                            <X
                                                class="h-3.5 w-3.5 text-slate-600"
                                            />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3 md:col-span-2">
                            <div class="flex items-center justify-between">
                                <Label>{{
                                    t('restaurantMenu.ingredients')
                                }}</Label>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="addIngredient"
                                    >{{ t('common.add') }}</Button
                                >
                            </div>
                            <div
                                v-for="(ing, idx) in form.ingredients"
                                :key="idx"
                                class="grid gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 md:grid-cols-3"
                            >
                                <select
                                    v-model="ing.product_id"
                                    class="w-full rounded-md border border-input bg-white px-3 py-2 text-sm"
                                >
                                    <option value="">
                                        {{ t('restaurantMenu.rawMaterial') }}
                                    </option>
                                    <option
                                        v-for="p in products"
                                        :key="p.id"
                                        :value="p.id"
                                    >
                                        {{ p[locale] || p.name_tr }}
                                    </option>
                                </select>
                                <Input
                                    v-model="ing.quantity"
                                    type="number"
                                    step="0.0001"
                                    :placeholder="t('common.quantity')"
                                />
                                <Button
                                    type="button"
                                    variant="destructive"
                                    @click="removeIngredient(idx)"
                                    >{{ t('common.delete') }}</Button
                                >
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div class="space-y-4 xl:sticky xl:top-4 xl:self-start">
                    <Card>
                        <CardHeader
                            ><CardTitle>{{
                                t('common.actions')
                            }}</CardTitle></CardHeader
                        >
                        <CardContent class="space-y-2">
                            <Button
                                type="submit"
                                class="w-full"
                                :disabled="form.processing"
                                >{{ t('common.save') }}</Button
                            >
                            <Link
                                href="/warehouse/restaurant-menu"
                                class="block"
                                ><Button
                                    type="button"
                                    variant="outline"
                                    class="w-full"
                                    >{{ t('common.cancel') }}</Button
                                ></Link
                            >
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader
                            ><CardTitle>{{
                                t('restaurantMenu.selectCover')
                            }}</CardTitle></CardHeader
                        >
                        <CardContent>
                            <div class="text-sm text-slate-600">
                                {{
                                    imagePreviews.length > 0
                                        ? `${imagePreviews.length} images uploaded`
                                        : t('restaurantMenu.foodImages')
                                }}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
