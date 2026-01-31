<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { index, store } from '@/actions/App/Http/Controllers/Warehouse/UnitController';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

const { t } = useI18n();
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.units'), href: index.url() }, { title: t('units.createUnit') }];
const form = useForm({ name_tr: '', name_en: '', symbol: '', base_unit_id: null as number | null, ratio_to_base: 1, is_active: true });
function submit() {
    form.post(store.url());
}
</script>

<template>
    <Head :title="t('units.createUnit')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
            <Card>
                <CardHeader><CardTitle>{{ t('units.createUnit') }}</CardTitle><CardDescription>{{ t('units.title') }}</CardDescription></CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2"><Label for="name_tr">{{ t('products.nameTr') }}</Label><Input id="name_tr" v-model="form.name_tr" required /></div>
                        <div class="space-y-2"><Label for="name_en">{{ t('products.nameEn') }}</Label><Input id="name_en" v-model="form.name_en" required /></div>
                        <div class="space-y-2"><Label for="symbol">{{ t('units.symbol') }}</Label><Input id="symbol" v-model="form.symbol" required /></div>
                        <div class="flex items-center space-x-2 md:col-span-2">
                            <Checkbox :checked="form.is_active" @update:checked="(v: boolean) => (form.is_active = v)" />
                            <Label>{{ t('common.active') }}</Label>
                        </div>
                        <div class="flex gap-2 md:col-span-2">
                            <Button type="submit" :disabled="form.processing">{{ t('common.save') }}</Button>
                            <Link :href="index.url()"><Button type="button" variant="outline">{{ t('common.cancel') }}</Button></Link>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
