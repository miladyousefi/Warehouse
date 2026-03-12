<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AppPageContent from '@/components/AppPageContent.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const { t } = useI18n();
const locale = computed(() =>
    useI18n().locale.value === 'tr' ? 'name_tr' : 'name_en',
);

const props = defineProps<{
    items: {
        data: Array<Record<string, any>>;
        links: Array<{ url: string | null; label: string }>;
    };
    categories: Array<Record<string, any>>;
    setting: {
        layout_type: string;
        is_public: boolean;
    };
    shareUrl: string | null;
    templates: Array<{ id: string; label: string }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('nav.restaurantMenu'), href: '/warehouse/restaurant-menu' },
];
const search = ref('');
const categoryIcons = [
    '🍽️',
    '🥗',
    '🥩',
    '🍔',
    '🍕',
    '🍰',
    '☕',
    '🍹',
    '🥤',
    '🍜',
];
const categoryForm = useForm({
    name_tr: '',
    name_en: '',
    icon: '🍽️',
    image: null as File | null,
    sort_order: 0,
    is_active: true,
});
const layoutForm = useForm({
    layout_type: props.setting.layout_type,
    is_public: props.setting.is_public,
});

function doSearch() {
    router.get('/warehouse/restaurant-menu', {
        search: search.value || undefined,
    });
}

function saveLayout() {
    layoutForm.put('/warehouse/restaurant-menu/layout', {
        forceFormData: true,
        preserveScroll: true,
    });
}

function addCategory() {
    categoryForm.post('/warehouse/restaurant-menu/categories', {
        forceFormData: true,
        onSuccess: () => {
            categoryForm.reset();
            categoryForm.icon = '🍽️';
            categoryForm.image = null;
        },
    });
}

function destroyItem(id: number) {
    if (confirm(t('common.confirmDelete')))
        router.delete(`/warehouse/restaurant-menu/${id}`);
}

async function copyShareUrl() {
    if (!props.shareUrl) return;
    await navigator.clipboard.writeText(props.shareUrl);
}

function setCategoryImage(event: Event) {
    const target = event.target as HTMLInputElement;
    categoryForm.image = target.files?.[0] ?? null;
}
</script>

<template>
    <Head :title="t('restaurantMenu.title')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppPageContent>
            <template #header>
                <div class="p-4 pb-0 md:p-6 md:pb-0">
                    <div>
                        <h1 class="text-xl font-semibold">
                            {{ t('restaurantMenu.title') }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            {{ t('restaurantMenu.menuSubtitle') }}
                        </p>
                    </div>
                </div>
            </template>
            <div class="space-y-4 p-4 pt-4 md:p-6 md:pt-4">
                <div class="flex flex-wrap gap-2">
                    <Link href="/warehouse/restaurant-menu/show"
                        ><Button variant="outline">{{
                            t('restaurantMenu.menuView')
                        }}</Button></Link
                    >
                    <a
                        v-if="shareUrl"
                        :href="shareUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        ><Button variant="outline">{{
                            t('restaurantMenu.publicLink')
                        }}</Button></a
                    >
                    <Link href="/warehouse/restaurant-menu/create"
                        ><Button>{{
                            t('restaurantMenu.addFood')
                        }}</Button></Link
                    >
                </div>

                <div class="grid gap-4 xl:grid-cols-5">
                    <Card class="xl:col-span-3">
                        <CardHeader
                            ><CardTitle>{{
                                t('restaurantMenu.layoutSettings')
                            }}</CardTitle></CardHeader
                        >
                        <CardContent class="space-y-4">
                            <Label>{{ t('restaurantMenu.layoutType') }}</Label>
                            <select
                                v-model="layoutForm.layout_type"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option
                                    v-for="tpl in templates"
                                    :key="tpl.id"
                                    :value="tpl.id"
                                >
                                    {{ tpl.label }}
                                </option>
                            </select>

                            <label class="flex items-center gap-2 text-sm"
                                ><input
                                    v-model="layoutForm.is_public"
                                    type="checkbox"
                                />{{ t('restaurantMenu.publicMenu') }}</label
                            >

                            <div class="rounded-md border p-3 text-xs">
                                <div class="mb-2 font-medium">
                                    {{ t('restaurantMenu.publicLink') }}
                                </div>
                                <div class="break-all text-muted-foreground">
                                    {{ shareUrl || '-' }}
                                </div>
                                <Button
                                    class="mt-2"
                                    variant="outline"
                                    size="sm"
                                    :disabled="!shareUrl"
                                    @click="copyShareUrl"
                                    >{{ t('restaurantMenu.copyLink') }}</Button
                                >
                            </div>

                            <Button
                                :disabled="layoutForm.processing"
                                @click="saveLayout"
                                >{{ t('common.save') }}</Button
                            >
                        </CardContent>
                    </Card>

                    <Card class="xl:col-span-2">
                        <CardHeader
                            ><CardTitle>{{
                                t('restaurantMenu.addCategory')
                            }}</CardTitle></CardHeader
                        >
                        <CardContent class="space-y-3">
                            <Input
                                v-model="categoryForm.name_tr"
                                :placeholder="t('restaurantMenu.nameTr')"
                            />
                            <Input
                                v-model="categoryForm.name_en"
                                :placeholder="t('restaurantMenu.nameEn')"
                            />
                            <div class="space-y-2">
                                <Label>{{
                                    t('restaurantMenu.categoryIcon')
                                }}</Label>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="icon in categoryIcons"
                                        :key="icon"
                                        type="button"
                                        class="h-9 w-9 rounded-md border text-lg"
                                        :class="
                                            categoryForm.icon === icon
                                                ? 'border-primary ring-2 ring-primary/40'
                                                : 'border-input'
                                        "
                                        @click="categoryForm.icon = icon"
                                    >
                                        {{ icon }}
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <Label>{{
                                    t('restaurantMenu.categoryImage')
                                }}</Label>
                                <Input
                                    type="file"
                                    accept="image/*"
                                    @change="setCategoryImage"
                                />
                            </div>
                            <Button
                                :disabled="categoryForm.processing"
                                @click="addCategory"
                                >{{ t('common.add') }}</Button
                            >
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <div
                            class="flex flex-col gap-2 sm:flex-row sm:items-center"
                        >
                            <Input
                                v-model="search"
                                :placeholder="t('common.search')"
                                class="max-w-sm"
                            />
                            <Button variant="outline" @click="doSearch">{{
                                t('common.search')
                            }}</Button>
                        </div>
                    </CardHeader>
                    <CardContent class="hidden md:block">
                        <Table class="rounded-md border">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{{
                                        t('common.name')
                                    }}</TableHead>
                                    <TableHead>{{
                                        t('restaurantMenu.category')
                                    }}</TableHead>
                                    <TableHead class="text-right">{{
                                        t('restaurantMenu.salePrice')
                                    }}</TableHead>
                                    <TableHead class="text-right">{{
                                        t('restaurantMenu.foodCost')
                                    }}</TableHead>
                                    <TableHead class="text-right">{{
                                        t('restaurantMenu.profit')
                                    }}</TableHead>
                                    <TableHead class="text-right">{{
                                        t('common.actions')
                                    }}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="item in items.data"
                                    :key="item.id"
                                >
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <img
                                                v-if="item.image_url"
                                                :src="item.image_url"
                                                class="h-8 w-8 rounded object-cover"
                                                alt="food"
                                            />
                                            <span>{{
                                                item[locale] || item.name_tr
                                            }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell>{{
                                        item.category?.[locale] || '-'
                                    }}</TableCell>
                                    <TableCell class="text-right">{{
                                        Number(item.sale_price).toFixed(2)
                                    }}</TableCell>
                                    <TableCell class="text-right">{{
                                        Number(item.food_cost || 0).toFixed(2)
                                    }}</TableCell>
                                    <TableCell class="text-right">{{
                                        Number(item.profit || 0).toFixed(2)
                                    }}</TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <Link
                                                :href="`/warehouse/restaurant-menu/${item.id}/edit`"
                                                ><Button
                                                    size="sm"
                                                    variant="outline"
                                                    >{{
                                                        t('common.edit')
                                                    }}</Button
                                                ></Link
                                            >
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                @click="destroyItem(item.id)"
                                                >{{
                                                    t('common.delete')
                                                }}</Button
                                            >
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                    <CardContent class="space-y-3 md:hidden">
                        <article
                            v-for="item in items.data"
                            :key="item.id"
                            class="rounded-md border p-3"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-2">
                                    <img
                                        v-if="item.image_url"
                                        :src="item.image_url"
                                        class="h-10 w-10 rounded object-cover"
                                        alt="food"
                                    />
                                    <div class="min-w-0">
                                        <h3 class="truncate font-semibold">
                                            {{ item[locale] || item.name_tr }}
                                        </h3>
                                        <p
                                            class="truncate text-xs text-muted-foreground"
                                        >
                                            {{ item.category?.[locale] || '-' }}
                                        </p>
                                    </div>
                                </div>
                                <strong>{{
                                    Number(item.sale_price).toFixed(2)
                                }}</strong>
                            </div>
                            <div class="mt-2 grid grid-cols-2 gap-2 text-xs">
                                <div class="rounded-md bg-muted p-2">
                                    {{ t('restaurantMenu.foodCost') }}:
                                    {{ Number(item.food_cost || 0).toFixed(2) }}
                                </div>
                                <div class="rounded-md bg-muted p-2">
                                    {{ t('restaurantMenu.profit') }}:
                                    {{ Number(item.profit || 0).toFixed(2) }}
                                </div>
                            </div>
                            <div class="mt-3 flex gap-2">
                                <Link
                                    :href="`/warehouse/restaurant-menu/${item.id}/edit`"
                                    class="flex-1"
                                    ><Button
                                        size="sm"
                                        variant="outline"
                                        class="w-full"
                                        >{{ t('common.edit') }}</Button
                                    ></Link
                                >
                                <Button
                                    size="sm"
                                    variant="destructive"
                                    class="flex-1"
                                    @click="destroyItem(item.id)"
                                    >{{ t('common.delete') }}</Button
                                >
                            </div>
                        </article>
                    </CardContent>
                </Card>
            </div>
        </AppPageContent>
    </AppLayout>
</template>
