<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { index, store } from '@/actions/App/Http/Controllers/Warehouse/SupplierController';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

const { t } = useI18n();
const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.suppliers'), href: index.url() }, { title: t('suppliers.createSupplier') }];
const form = useForm({ name: '', contact_person: '', email: '', phone: '', address: '', tax_number: '', tax_office: '', notes: '', is_active: true });
function submit() {
    form.post(store.url());
}
</script>

<template>
    <Head :title="t('suppliers.createSupplier')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
            <Card>
                <CardHeader><CardTitle>{{ t('suppliers.createSupplier') }}</CardTitle><CardDescription>{{ t('suppliers.title') }}</CardDescription></CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2"><Label for="name">{{ t('common.name') }}</Label><Input id="name" v-model="form.name" required /></div>
                        <div class="space-y-2"><Label for="contact_person">{{ t('suppliers.contactPerson') }}</Label><Input id="contact_person" v-model="form.contact_person" /></div>
                        <div class="space-y-2"><Label for="email">Email</Label><Input id="email" v-model="form.email" type="email" /></div>
                        <div class="space-y-2"><Label for="phone">{{ t('suppliers.phone') }}</Label><Input id="phone" v-model="form.phone" /></div>
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
